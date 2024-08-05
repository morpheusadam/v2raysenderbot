<?php

require_once 'vendor/autoload.php';

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\Update;

$config = require_once 'config.php';

class v2raySenderBot
{
    private $bot;

    public function __construct($config)
    {
        $this->bot = new BotApi($config['bot_token']);
    }

    public function sendMessage($chat_id, $message)
    {
        $this->bot->sendMessage($chat_id, $message);
    }

    public function handleWebhook()
    {
        $update = json_decode(file_get_contents('php://input'), true);
        if ($update) {
            $message = $update['message'];
            if ($message && $message['text'] === '/start') {
                $chat_id = $message['chat']['id'];
                $this->sendMessage($chat_id, 'سلام');
            }
        }
    }
}

$bot = new v2raySenderBot($config);
$bot->handleWebhook();

// ... existing code ...