<?php
declare(strict_types=1);

namespace App\Services;

class OpenRouterClient
{
    private string $apiKey;
    private string $model;
    private string $endpoint = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENROUTER_API_KEY'] ?? '';
        $this->model  = $_ENV['OPENROUTER_MODEL'] ?? 'mistralai/mistral-7b-instruct';
    }

    /**
     * @param array $messages  [['role' => 'system|user', 'content' => '...']]
     * @throws \RuntimeException on HTTP error or curl failure
     */
    public function chat(array $messages): string
    {
        $payload = json_encode([
            'model'    => $this->model,
            'messages' => $messages,
        ]);

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 55,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'HTTP-Referer: ' . ($_ENV['BASE_URL'] ?? 'https://whatypie.in'),
                'X-Title: WhatyPie Blog Generator',
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \RuntimeException("cURL error: {$curlError}");
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException("OpenRouter API error {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);

        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new \RuntimeException("Unexpected API response structure: {$response}");
        }

        return $decoded['choices'][0]['message']['content'];
    }
}
