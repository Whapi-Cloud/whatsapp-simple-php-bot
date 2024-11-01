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
        $ch = curl_init('https://gate.whapi.cloud/health');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        if (!isset($data['status']['text']) || $data['status']['text'] !== 'AUTH') {
            throw new \Exception('Channel not auth');
        }
        curl_close($ch);
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
        $response = curl_exec($ch);
        $data = json_decode($response, true);
        curl_close($ch);

        return $data['webhooks'] ?? [];
    }

    // Set the webhook URL on the channel page in the dashboard or use the setWebHook() function (not used now)
    public function setWebHook(): bool {
        $fullWebhookUrl = $this->config['app_url'] . '/hook';
        $currentHooks = $this->getWebHooks();

        if (in_array($fullWebhookUrl, array_column($currentHooks, 'url'))) {
            return true;
        }

        $hookExists = false;
        foreach ($currentHooks as &$hook) {
            if (strpos($hook['url'], 'ngrok') !== false) {
                $hook['url'] = $fullWebhookUrl;
                $hookExists = true;
                break;
            }
        }

        if (!$hookExists) {
            $currentHooks[] = [
                'events' => [
                    ['type' => 'messages', 'method' => 'post']
                ],
                'mode' => 'body',
                'url' => $fullWebhookUrl
            ];
        }

        $ch = curl_init('https://gate.whapi.cloud/settings');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->token,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'webhooks' => $currentHooks
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return 'success';
        }

        throw new \Exception('Failed to send image');
    }
}