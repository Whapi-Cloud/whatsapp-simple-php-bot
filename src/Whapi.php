<?php

namespace App;

class Whapi {
    public function whatsapp() {
        $rawData = file_get_contents('php://input');
        $request = json_decode($rawData, true);
        
        $message = $request['messages'][0] ?? null;
        if (!$message) {
            return $this->response('OK', 200);
        }
        // Outgoing message handling, so that your bot doesn't react to its own messages.
        $isMe = $message['from_me'];
        if ($isMe) {
            return $this->response('OK', 200);
        }
        // Handling the type of incoming message, if it will not be text - then do not react to it.
        // You can change this to increase your customers' engagement and experience, such as handling media messages, etc.
        // Familiarize yourself with the message types in the documentation. See examples of inbound callbacks here: https://support.whapi.cloud/help-desk/receiving/webhooks/incoming-webhooks-format/incoming-message
        $type = $message['type'];
        if ($type !== 'text') {
            return $this->response('OK', 200);
        }

        $senderPhone = str_replace('@s.whatsapp.net', '', $message['chat_id']); // Getting the number of the contact who wrote to you from the chat. Learn more about what a chat_id is: https://support.whapi.cloud/help-desk/faq/chat-id.-what-is-it-and-how-to-get-it
        $receivedText = strtolower($message['text']['body']); // Retrieving text from an incoming message

        $config = require __DIR__ . '/../config/config.php';
        $channel = new Channel($config['whatsapp_token']);
        $channel->checkHealth(); // It is necessary to check that the API channel is working correctly

        // The main cycle of the bot is to check and react to the incoming message depending on the text
        switch ($receivedText) {
            case 'help':
                $channel->sendMessage($senderPhone, 'Text1'); // If the bot receives the message 'help', it will send the message 'Text1'
                break;
            case 'command':
                $channel->sendMessage($senderPhone, 'Text2');
                break;
            case 'image':
                $channel->sendLocalJPG(__DIR__ . '/../public/images/example.jpeg', $senderPhone, 'Caption'); // We encode the picture in base64, so it's easier to send a media message.
                break;
            default:
                $channel->sendMessage($senderPhone, 'Unknown command'); // On an unknown team, it's best to send navigation information to help your customer navigate
                break;
        }

        return $this->response('OK', 200);
    }

    private function response($message, $code) {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['message' => $message]);
        exit;
    }
}