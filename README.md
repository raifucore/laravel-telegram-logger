# Laravel Telegram Logger

Send Laravel logs to Telegram chat via Telegram Bot API.

## Install

```bash
composer require raifucore/laravel-telegram-logger
```

Publish package config and views:

```bash
php artisan vendor:publish --provider="RaifuCore\TelegramLogger\ServiceProvider"
```

## Configure environment

Add these variables to your `.env`:

```dotenv
TELEGRAM_LOGGER_ENABLE=true
TELEGRAM_LOGGER_BOT_TOKEN=7123456789:ABHo3qcH6G1wMi4VPc8xxZZ474UizrF5e6Dk
TELEGRAM_LOGGER_CHAT_ID=-1112223334445
TELEGRAM_LOGGER_MESSAGE_THREAD_ID=

TELEGRAM_LOGGER_QUEUE=default
TELEGRAM_LOGGER_QUEUE_CONNECTION=sync
TELEGRAM_LOGGER_TIMEOUT=5

# optional
TELEGRAM_LOGGER_PROXY=
```

### Variables

- `TELEGRAM_LOGGER_ENABLE`: enable or disable telegram logging.
- `TELEGRAM_LOGGER_BOT_TOKEN`: Telegram bot token from `@BotFather`.
- `TELEGRAM_LOGGER_CHAT_ID`: target chat id.
- `TELEGRAM_LOGGER_MESSAGE_THREAD_ID`: optional topic id for forum chats.
- `TELEGRAM_LOGGER_QUEUE`: queue name for log delivery jobs.
- `TELEGRAM_LOGGER_QUEUE_CONNECTION`: queue connection (`sync`, `redis`, etc.).
- `TELEGRAM_LOGGER_TIMEOUT`: HTTP timeout (seconds) for Telegram API calls.
- `TELEGRAM_LOGGER_PROXY`: optional proxy URL, for example:
    - `tcp://host:port`
    - `tcp://user:pass@host:port`
    - `socks5://user:pass@host:port`

## Configure `config/logging.php`

Add a custom channel:

```php
'telegram' => [
    'driver' => 'custom',
    'via' => \RaifuCore\TelegramLogger\Logger::class,
    'level' => 'debug',
],
```

If your default channel is `stack`, add `telegram` there:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'telegram'],
],
```

Or set default channel:

```dotenv
LOG_CHANNEL=telegram
```

## Usage

Use Laravel `Log` as usual:

```php
use Illuminate\Support\Facades\Log;

Log::error('Payment failed', ['order_id' => 123]);
Log::channel('telegram')->warning('Telegram channel only');
```

## Queue behavior

Messages are dispatched as jobs (`RaifuCore\TelegramLogger\Job`).

- With `TELEGRAM_LOGGER_QUEUE_CONNECTION=sync`, jobs are executed immediately.
- With async connections (for example `redis`), jobs are pushed to queue workers.

## Templates

By default package uses Blade template:

```php
'template' => 'telegram_logger::standard',
```

To customize templates:

1. Publish package views.
2. Create your own template in `resources/views/vendor/telegram_logger/`.
3. Set `template` in `config/telegram_logger.php` or at runtime:

```php
config(['telegram_logger.template' => 'telegram_logger::minimal']);
```

## Per-level overrides

You can override parameters per log level in `config/telegram_logger.php`:

```php
'levels' => [
    'error' => [
        'chat_id' => env('TELEGRAM_LOGGER_ERROR_CHAT_ID'),
        'options' => [
            'disable_notification' => true,
        ],
    ],
],
```

Supported keys per level:

- `token`
- `chat_id`
- `message_thread_id`
- `template`
- `options`

## Create Telegram bot

1. Open `@BotFather`.
2. Run `/newbot`.
3. Configure bot name and username.
4. Copy token to `.env`.
5. Open your bot and send `/start`.