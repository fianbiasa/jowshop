<?php

namespace App\Services;

use App\Enums\AiProvider;
use App\Exceptions\AiGenerationException;
use App\Models\AiProviderSetting;
use Illuminate\Support\Facades\Http;

class AiProviderClient
{
    /**
     * Send a prompt to the configured AI provider and return the generated text.
     *
     * @return array{content: string, tokens_input: int|null, tokens_output: int|null}
     */
    public function generate(AiProviderSetting $setting, string $prompt): array
    {
        return match ($setting->provider) {
            AiProvider::OpenAi => $this->generateWithOpenAi($setting, $prompt),
            AiProvider::Anthropic => $this->generateWithAnthropic($setting, $prompt),
            AiProvider::Gemini => $this->generateWithGemini($setting, $prompt),
        };
    }

    /**
     * @return array{content: string, tokens_input: int|null, tokens_output: int|null}
     */
    private function generateWithOpenAi(AiProviderSetting $setting, string $prompt): array
    {
        $response = Http::withToken($setting->api_key)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $setting->default_model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw AiGenerationException::fromResponse($response->status(), $response->body());
        }

        return [
            'content' => (string) $response->json('choices.0.message.content', ''),
            'tokens_input' => $response->json('usage.prompt_tokens'),
            'tokens_output' => $response->json('usage.completion_tokens'),
        ];
    }

    /**
     * @return array{content: string, tokens_input: int|null, tokens_output: int|null}
     */
    private function generateWithAnthropic(AiProviderSetting $setting, string $prompt): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $setting->api_key,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => $setting->default_model,
            'max_tokens' => 4096,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        if ($response->failed()) {
            throw AiGenerationException::fromResponse($response->status(), $response->body());
        }

        return [
            'content' => (string) $response->json('content.0.text', ''),
            'tokens_input' => $response->json('usage.input_tokens'),
            'tokens_output' => $response->json('usage.output_tokens'),
        ];
    }

    /**
     * @return array{content: string, tokens_input: int|null, tokens_output: int|null}
     */
    private function generateWithGemini(AiProviderSetting $setting, string $prompt): array
    {
        $response = Http::withQueryParameters(['key' => $setting->api_key])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$setting->default_model}:generateContent",
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                ],
            );

        if ($response->failed()) {
            throw AiGenerationException::fromResponse($response->status(), $response->body());
        }

        return [
            'content' => (string) $response->json('candidates.0.content.parts.0.text', ''),
            'tokens_input' => $response->json('usageMetadata.promptTokenCount'),
            'tokens_output' => $response->json('usageMetadata.candidatesTokenCount'),
        ];
    }
}
