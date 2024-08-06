<?php

require_once 'vendor/autoload.php';

use TelegramBot\Api\BotApi;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;
use TelegramBot\Api\Exception;

$config = require_once 'config.php';

class v2raySenderBot
{
    private $bot;
    private $botToken;
    private $awaitingData = [];

    public function __construct($config)
    {
        $this->bot = new BotApi($config['bot_token']);
        $this->botToken = $config['bot_token'];
    }

    public function sendMessage($chat_id, $message, $keyboard = null)
    {
        try {
            // تنظیمات cURL برای افزایش زمان محدودیت
            $this->bot->setCurlOption(CURLOPT_TIMEOUT, 30); // افزایش زمان محدودیت به 30 ثانیه
            $this->bot->sendMessage($chat_id, $message, 'HTML', false, null, $keyboard);
        } catch (Exception $e) {
            error_log('Error sending message: ' . $e->getMessage(), 3, '/var/www/v2raysenderbot/custom_error.log');
        }
    }

    public function sendWelcomeMessage($chat_id)
    {
        $message = 'سلام ادمین خوش اومدی';
        $keyboard = new ReplyKeyboardMarkup(
            [
                ['ارسال دیتا همراه اول'],
                ['ارسال دیتای ایرانسل'],
                ['ارسال دیتای ارسال دیتا مخابرات']
            ],
            true,
            true
        );
        $this->sendMessage($chat_id, $message, $keyboard);
    }

    public function handleWebhook()
    {
        $update = json_decode(file_get_contents('php://input'), true);
        if ($update) {
            $message = $update['message'];
            if ($message) {
                $chat_id = $message['chat']['id'];
                $text = $message['text'] ?? null;
                $document = $message['document'] ?? null;

                if ($text === '/start') {
                    $this->sendWelcomeMessage($chat_id);
                } elseif ($text === 'ارسال دیتا همراه اول') {
                    $this->sendMessage($chat_id, 'لطفا فایل .txt خود را ارسال کنید');
                    $this->awaitingData[$chat_id] = true;
                } elseif (isset($this->awaitingData[$chat_id]) && $this->awaitingData[$chat_id] && $document) {
                    $file_id = $document['file_id'];
                    $file_info = $this->bot->getFile($file_id);
                    $file_path = $file_info->getFilePath();
                    $file_url = "https://api.telegram.org/file/bot{$this->botToken}/{$file_path}";

                    $this->saveFile($chat_id, $file_url);
                    unset($this->awaitingData[$chat_id]);
                    $this->sendMessage($chat_id, 'فایل شما ذخیره شد.');
                }
                // می‌توانید شرایط دیگری را برای دکمه‌های دیگر اضافه کنید
            }
        }
    }

    private function saveFile($chat_id, $file_url)
    {
        $file_content = file_get_contents($file_url);
        if ($file_content === false) {
            error_log("Failed to download file from URL: $file_url", 3, '/var/www/v2raysenderbot/custom_error.log');
            return;
        }

        $file_path = "/var/www/v2raysenderbot/user_data_{$chat_id}.txt";
        if (file_put_contents($file_path, $file_content) === false) {
            error_log("Failed to save file to path: $file_path", 3, '/var/www/v2raysenderbot/custom_error.log');
            error_log("File content: " . substr($file_content, 0, 100), 3, '/var/www/v2raysenderbot/custom_error.log'); // لاگ کردن بخشی از محتوای فایل برای بررسی
            error_log("File permissions: " . substr(sprintf('%o', fileperms('/var/www/v2raysenderbot')), -4), 3, '/var/www/v2raysenderbot/custom_error.log'); // لاگ کردن دسترسی‌های پوشه
        } else {
            error_log("File saved successfully to path: $file_path", 3, '/var/www/v2raysenderbot/custom_error.log');
        }
    }
}

//var_dump(file_get_contents('php://input'));
$bot = new v2raySenderBot($config);
$bot->handleWebhook();

// ... existing code ...