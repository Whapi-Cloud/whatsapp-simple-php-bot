<?php

namespace App;

class Channel {
    private string $token;
    private $config;

    public function __construct(string $token) {
        $this->token = $token;
        $this->config = require __DIR__ . '/../config/config.php';
    }

    public function checkHealth() {
        // Channel check: wake up channel and specify channel_type
        $url = 'https://gate.whapi.cloud/health?wakeup=true&channel_type=web';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $this->applyTlsOptions($ch);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = $errno ? curl_error($ch) : null;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            throw new \Exception('Health check failed: curl error #' . $errno . ' ' . $error);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \Exception('Health check failed: HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            throw new \Exception('Health check failed: non-JSON response');
        }
        if (!isset($data['status']['text']) || $data['status']['text'] !== 'AUTH') {
            $statusTxt = $data['status']['text'] ?? 'UNKNOWN';
            $statusCode = $data['status']['code'] ?? 'N/A';
            throw new \Exception('Channel not auth (status=' . $statusTxt . ', code=' . $statusCode . ')');
        }
    }

    private function applyTlsOptions($ch): void {
        // Prefer explicit CA bundle if provided in config (especially for Windows)
        $caBundle = $this->config['ca_bundle'] ?? null;
        if (is_string($caBundle) && $caBundle !== '' && file_exists($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        }
    }

    public function sendMessage($to, $body): bool {
        $ch = curl_init('https://gate.whapi.cloud/messages/text');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'typing_time' => 0,
            'to' => $to,
            'body' => $body
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->applyTlsOptions($ch);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['sent'] ?? false;
    }

    public function getWebHooks(): array {
        $ch = curl_init('https://gate.whapi.cloud/settings');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->applyTlsOptions($ch);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['webhooks'] ?? [];
    }

    // Set the webhook URL on the channel using API
    public function setWebHook(): bool {
        $fullWebhookUrl = rtrim($this->config['app_url'], '/');
        $fullWebhookUrl .= '/whatsapp';

        $payload = [
            'webhooks' => [
                [
                    'mode' => 'body',
                    'events' => [
                        ['type' => 'messages', 'method' => 'post']
                    ],
                    'url' => $fullWebhookUrl
                ]
            ]
        ];

        $ch = curl_init('https://gate.whapi.cloud/settings');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->applyTlsOptions($ch);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    public function sendLocalJPG($filePath, $to, $caption = null): string {
        if (!file_exists($filePath)) {
            return 'File does not exist!';
        }

        $base64 = base64_encode(file_get_contents($filePath));
        if (empty($base64)) {
            return 'File is empty!';
        }

        $fileName = basename($filePath);
        $media = "data:image/jpeg;name={$fileName};base64,{$base64}";

        $ch = curl_init('https://gate.whapi.cloud/messages/image');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'to' => $to,
            'media' => $media,
            'caption' => $caption
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $this->applyTlsOptions($ch);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return 'success';
        }

        throw new \Exception('Failed to send image');
    }
}