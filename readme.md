

Step 1: composer require irazasyed/telegram-bot-sdk

Step 2: set config/app.php
  - Telegram\Bot\Laravel\TelegramServiceProvider::class,
  - 'Telegram' => Telegram\Bot\Laravel\Facades\Telegram::class,

Step 3: publish config
  - php artisan vendor:publish --provider="Telegram\Bot\Laravel\TelegramServiceProvider"
