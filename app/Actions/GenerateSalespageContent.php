<?php

namespace App\Actions;

use App\Enums\SalespageStyle;
use App\Exceptions\AiGenerationException;
use App\Models\AiGenerationLog;
use App\Models\AiProviderSetting;
use App\Models\Funnel;
use App\Models\Salespage;
use App\Services\AiProviderClient;
use App\Support\ContentBlockSanitizer;

class GenerateSalespageContent
{
    public function __construct(private readonly AiProviderClient $client) {}

    /**
     * Generate (or regenerate) a funnel's salespage content using AI, logging
     * the attempt regardless of outcome. The style is chosen by the admin
     * beforehand (see SalespageStyle) — the AI only ever fills copy into one
     * of the curated visual styles, it never invents its own design.
     *
     * @throws AiGenerationException if the AI provider request fails or returns unparsable content.
     */
    public function __invoke(Funnel $funnel, AiProviderSetting $setting, SalespageStyle $style, ?string $brief = null): Salespage
    {
        $prompt = $this->buildPrompt($funnel, $style, $brief);
        $rawContent = null;

        try {
            $result = $this->client->generate($setting, $prompt);
            $rawContent = $result['content'];
            $blocks = $this->parseBlocks($rawContent);

            $salespage = Salespage::query()->updateOrCreate(
                ['funnel_id' => $funnel->id],
                [
                    'title' => $funnel->name,
                    'content' => $blocks,
                    'style' => $style,
                    'generated_by_ai' => true,
                ],
            );

            AiGenerationLog::query()->create([
                'ai_provider_setting_id' => $setting->id,
                'salespage_id' => $salespage->id,
                'prompt' => $prompt,
                'response_excerpt' => str($rawContent)->limit(2000)->toString(),
                'tokens_input' => $result['tokens_input'],
                'tokens_output' => $result['tokens_output'],
                'status' => 'success',
            ]);

            return $salespage;
        } catch (AiGenerationException $exception) {
            AiGenerationLog::query()->create([
                'ai_provider_setting_id' => $setting->id,
                'salespage_id' => $funnel->salespage?->id,
                'prompt' => $prompt,
                // Log the raw AI response (not just the exception message) when
                // parsing is what failed — without it, a parse failure is
                // undiagnosable after the fact since the raw content is gone.
                'response_excerpt' => str($rawContent ?? $exception->getMessage())->limit(2000)->toString(),
                'status' => 'failed',
            ]);

            throw $exception;
        }
    }

    private function buildPrompt(Funnel $funnel, SalespageStyle $style, ?string $brief): string
    {
        $product = $funnel->product;

        return implode("\n", array_filter([
            'Buatkan copy salespage untuk produk berikut, dalam Bahasa Indonesia.',
            "Nama produk: {$product?->name}",
            $product?->description ? "Deskripsi produk: {$product->description}" : null,
            $product ? "Harga: Rp{$product->price}" : null,
            $brief ? "Brief tambahan dari admin: {$brief}" : null,
            $this->toneGuidanceFor($style),
            'Balas HANYA dengan JSON array (tanpa markdown code fence, tanpa teks lain) berisi blok-blok salespage.',
            'Setiap elemen array berbentuk {"type": "...", "data": {...}}.',
            'Gunakan tipe blok berikut sesuai kebutuhan: headline ({"text"}), subheadline ({"text"}), benefit_list ({"items": [string]}), testimonial ({"name","quote"}), faq ({"items": [{"question","answer"}]}), guarantee ({"text"}), cta ({"label"}), divider ({}), spacer ({"height": "sm"|"md"|"lg"}).',
            'Jangan gunakan tipe blok "image" atau "video" — tidak ada aset gambar/video yang bisa dirujuk.',
        ]));
    }

    /**
     * The visual style is fixed (chosen by the admin beforehand); this only
     * nudges the copy's tone/length to match it so the generated content
     * feels native to the style it'll be rendered in.
     */
    private function toneGuidanceFor(SalespageStyle $style): string
    {
        return match ($style) {
            SalespageStyle::Bold => 'Gaya tulisan: mendesak dan penuh urgency/scarcity, kalimat pendek dan tegas, cocok untuk penawaran terbatas.',
            SalespageStyle::Editorial => 'Gaya tulisan: storytelling naratif yang lebih panjang, mengalir seperti artikel, cocok untuk produk edukasi/kelas.',
            SalespageStyle::Minimal => 'Gaya tulisan: singkat, padat, langsung ke inti, tanpa basa-basi berlebihan.',
            SalespageStyle::Ledger => 'Gaya tulisan: lugas dan transparan seperti nota/kwitansi, tekankan rincian harga dan bukti/jaminan, minim gimmick.',
        };
    }

    /**
     * @return array<int, array{type: string, data: array<int|string, mixed>}>
     */
    private function parseBlocks(string $content): array
    {
        $decoded = $this->decodeJsonArray($content);

        if (! is_array($decoded) || $decoded === []) {
            throw new AiGenerationException('AI response could not be parsed into salespage blocks.');
        }

        $blocks = [];

        foreach ($decoded as $block) {
            if (! is_array($block) || ! isset($block['type']) || ! is_string($block['type'])) {
                continue;
            }

            $blocks[] = [
                'type' => strip_tags($block['type']),
                'data' => ContentBlockSanitizer::sanitize($block['data'] ?? []),
            ];
        }

        if ($blocks === []) {
            throw new AiGenerationException('AI response did not contain any valid salespage blocks.');
        }

        return $blocks;
    }

    /**
     * Decode a JSON array out of the AI's raw response, tolerating the
     * conversational wrapping real models produce despite being told to
     * reply with JSON only (markdown code fences, and/or leading/trailing
     * prose like "Here's the salespage content: ... Let me know if...").
     */
    private function decodeJsonArray(string $content): mixed
    {
        $trimmed = trim($content);
        $trimmed = trim(preg_replace('/^```[a-z]*|```$/mi', '', $trimmed) ?? $trimmed);

        $decoded = json_decode($trimmed, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        // Fall back to extracting the outermost [...] from surrounding text.
        $start = strpos($trimmed, '[');
        $end = strrpos($trimmed, ']');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        return json_decode(substr($trimmed, $start, $end - $start + 1), true);
    }
}
