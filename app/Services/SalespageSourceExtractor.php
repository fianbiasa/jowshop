<?php

namespace App\Services;

use App\Exceptions\AiGenerationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser as PdfParser;

class SalespageSourceExtractor
{
    /**
     * Cap on how much extracted source text is fed into the AI prompt —
     * keeps token usage/cost bounded regardless of how large the page or
     * document is; the AI only needs enough material to write copy from.
     */
    private const MAX_CHARS = 15000;

    /**
     * Fetch a live URL and reduce it to plain readable text.
     *
     * Since the admin can point this at any URL, it's guarded against SSRF:
     * only http/https is allowed, and the resolved host must not be a
     * loopback/private/reserved address (blocks access to the server's own
     * internal network, e.g. localhost or a cloud metadata endpoint).
     *
     * @throws AiGenerationException
     */
    public function fromUrl(string $url): string
    {
        $this->assertSafeUrl($url);

        $response = Http::timeout(10)
            ->withOptions(['allow_redirects' => ['max' => 3]])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; JowshopSalespageBot/1.0)'])
            ->get($url);

        if ($response->failed()) {
            throw new AiGenerationException("Gagal mengambil konten dari URL (status {$response->status()}).");
        }

        return $this->truncate($this->htmlToText($response->body()));
    }

    /**
     * Extract plain text from an uploaded .txt/.md/.pdf source document.
     *
     * @throws AiGenerationException
     */
    public function fromDocument(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $text = match ($extension) {
            'txt', 'md' => (string) file_get_contents($file->getRealPath()),
            'pdf' => $this->extractPdfText($file),
            default => throw new AiGenerationException("Format dokumen .{$extension} tidak didukung."),
        };

        return $this->truncate(trim($text));
    }

    private function extractPdfText(UploadedFile $file): string
    {
        try {
            $pdf = (new PdfParser)->parseFile($file->getRealPath());

            return $pdf->getText();
        } catch (\Throwable $e) {
            throw new AiGenerationException('Gagal membaca isi dokumen PDF: '.$e->getMessage());
        }
    }

    /**
     * @throws AiGenerationException
     */
    private function assertSafeUrl(string $url): void
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? null;
        $host = $parts['host'] ?? null;

        if (! in_array($scheme, ['http', 'https'], true) || ! $host) {
            throw new AiGenerationException('URL sumber harus berupa alamat http/https yang valid.');
        }

        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        if ($ips === []) {
            throw new AiGenerationException('URL sumber tidak dapat diakses (host tidak dapat ditemukan).');
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new AiGenerationException('URL sumber mengarah ke alamat jaringan internal yang tidak diizinkan.');
            }
        }
    }

    /**
     * Strip an HTML document down to its readable body text — drops
     * script/style/nav/header/footer noise so the AI prompt isn't padded
     * with markup and site chrome instead of actual content.
     */
    private function htmlToText(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($document);
        foreach ($xpath->query('//script|//style|//nav|//header|//footer|//noscript') as $node) {
            $node->parentNode?->removeChild($node);
        }

        $text = $document->textContent ?? '';

        return trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n\s*\n+/', "\n", $text) ?? '') ?? '');
    }

    private function truncate(string $text): string
    {
        return mb_strlen($text) > self::MAX_CHARS
            ? mb_substr($text, 0, self::MAX_CHARS).'...'
            : $text;
    }
}
