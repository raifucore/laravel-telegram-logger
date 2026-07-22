# Laravel Telegram Logger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/raifucore/laravel-telegram-logger.svg)](https://packagist.org/packages/raifucore/laravel-telegram-logger)
[![PHP Version](https://img.shields.io/packagist/dependency-v/raifucore/laravel-telegram-logger/php.svg)](https://packagist.org/packages/raifucore/laravel-telegram-logger)
[![License](https://img.shields.io/packagist/l/raifucore/laravel-telegram-logger.svg)](https://packagist.org/packages/raifucore/laravel-telegram-logger)

Send Laravel logs to Telegram via the Telegram Bot API.

## Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Usage](#usage)
- [Templates](#templates)
- [Per-level overrides](#per-level-overrides)
- [Forum topics / targeted sending](#forum-topics--targeted-sending)
- [Queue behavior](#queue-behavior)
- [Message behavior](#message-behavior)
- [Testing](#testing)
- [Troubleshooting](#troubleshooting)

## Features

- Custom Laravel logging channel with Monolog 3
- Queued delivery (`sync` or async queue connections)
- Blade templates (`standard`, `minimal`, or your own)
- Per-level overrides (token, chat id, thread id, template, options)
- Forum topic routing via a reserved `telegram` context key
- Optional HTTP proxy and configurable request timeout
- Automatic splitting of long messages into chunks of up to 4096 bytes

## Requirements

- PHP `^8.1`
- Laravel `^10|^11|^12`
- Monolog `^3`
- Guzzle `^7`

## Installation

```bash
composer require raifucore/laravel-telegram-logger
```

The service provider is auto-discovered by Laravel.

Publish config and views:

```bash
php artisan vendor:publish --provider="RaifuCore\TelegramLogger\ServiceProvider"
```

Or publish selectively:

```bash
php artisan vendor:publish --provider="RaifuCore\TelegramLogger\ServiceProvider" --tag=config
php artisan vendor:publish --provider="RaifuCore\TelegramLogger\ServiceProvider" --tag=views
```

## Quick start

### 1. Create a bot and chat

1. Open [@BotFather](https://t.me/BotFather), run `/newbot`, and copy the token.
2. Add the bot to your private chat, group, or forum group.
3. For TG topics, make sure the bot can post in the target topic.
4. Put the bot token and chat id into `.env` (see below).

### 2. Configure `.env`

```dotenv
TELEGRAM_LOGGER_ENABLE=true
TELEGRAM_LOGGER_BOT_TOKEN=your-bot-token
TELEGRAM_LOGGER_CHAT_ID=-100xxxxxxxxxx
TELEGRAM_LOGGER_MESSAGE_THREAD_ID=

TELEGRAM_LOGGER_QUEUE=default
TELEGRAM_LOGGER_QUEUE_CONNECTION=sync
TELEGRAM_LOGGER_TIMEOUT=5

# optional
TELEGRAM_LOGGER_PROXY=

# optional: per-level forum topics (see below)
#TELEGRAM_LOGGER_DEBUG_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_INFO_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_NOTICE_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_WARNING_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_ERROR_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_CRITICAL_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_ALERT_MESSAGE_THREAD_ID=
#TELEGRAM_LOGGER_EMERGENCY_MESSAGE_THREAD_ID=

# optional: named topics defined in your config, e.g.
#TELEGRAM_LOGGER_TOPIC_PAYMENTS=
```

`TELEGRAM_LOGGER_ENABLE` defaults to `false`. The package sends nothing until it is set to `true`.

Topic-related variables (all optional):

- `TELEGRAM_LOGGER_<LEVEL>_MESSAGE_THREAD_ID` — the published config maps each log level (`DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `CRITICAL`, `ALERT`, `EMERGENCY`) to its own forum topic id. Leave them empty to send everything to the default destination. See [Per-level overrides](#per-level-overrides).
- Named topic variables (such as `TELEGRAM_LOGGER_TOPIC_PAYMENTS`) — only used if you define named topics in `config/telegram_logger.php`; the variable names are up to you. See [Forum topics / targeted sending](#forum-topics--targeted-sending).

### 3. Add a logging channel

In `config/logging.php`:

```php
'telegram' => [
    'driver' => 'custom',
    'via' => \RaifuCore\TelegramLogger\Logger::class,
    'level' => 'debug',
],
```

The `level` key is required — the handler reads it directly and fails without it.

If your default channel is `stack`, include `telegram` there:

```php
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'telegram'],
],
```

### 4. Send a test

```bash
php artisan tg-logger:test
```

The test command uses the default `Log` facade (`Log::debug()`, `Log::info()`, …), not `Log::channel('telegram')`. Add `telegram` to your default/`stack` channel, or the test will not reach Telegram.

## Configuration

Main settings live in `config/telegram_logger.php` and are usually driven by `.env`.


| Variable                            | Default   | Description                           |
| ----------------------------------- | --------- | ------------------------------------- |
| `TELEGRAM_LOGGER_ENABLE`            | `false`   | Enable Telegram logging               |
| `TELEGRAM_LOGGER_BOT_TOKEN`         | —         | Bot token from `@BotFather`           |
| `TELEGRAM_LOGGER_CHAT_ID`           | —         | Target chat id                        |
| `TELEGRAM_LOGGER_MESSAGE_THREAD_ID` | —         | Default forum topic id                |
| `TELEGRAM_LOGGER_QUEUE`             | `default` | Queue name for delivery jobs          |
| `TELEGRAM_LOGGER_QUEUE_CONNECTION`  | `sync`    | Queue connection (`sync`, `redis`, …) |
| `TELEGRAM_LOGGER_TIMEOUT`           | `5`       | HTTP timeout in seconds               |
| `TELEGRAM_LOGGER_PROXY`             | —         | Optional proxy URL                    |


Proxy examples:

- `tcp://host:port`
- `tcp://user:pass@host:port`
- `socks5://user:pass@host:port`

Per-level and named topic env vars are listed in [Configure .env](#2-configure-env).

### Telegram `sendMessage` options

Root and override configs accept Telegram Bot API options:

```php
'options' => [
    // 'disable_web_page_preview' => true,
    // 'disable_notification' => false,
],
```

See [sendMessage](https://core.telegram.org/bots/api#sendmessage).

Options are merged in this order (later wins): built-in default `parse_mode=html` → root `options` → level or topic `options`. So `parse_mode` defaults to `html` even when it is commented out in the config file, but you can override it via `options`. Keep in mind that the built-in templates rely on HTML tags.

## Usage

```php
use Illuminate\Support\Facades\Log;

Log::error('Payment failed', ['order_id' => 123]);
Log::channel('telegram')->warning('Telegram channel only');
```

## Templates

Built-in Blade templates:

- `telegram_logger::standard` (default) — app name, level, env, datetime, formatted message
- `telegram_logger::minimal` — app name, level, formatted message

Available view variables include:

- `appName`, `appEnv`
- `level_name`, `datetime`, `formatted`
- other fields from the Monolog record (`message`, `context`, `extra`, …)

To customize:

1. Publish views.
2. Edit or add a template under `resources/views/vendor/telegram_logger/`.
3. Point `template` in `config/telegram_logger.php` to your view:

```php
'template' => 'telegram_logger::minimal',
```

Or at runtime:

```php
config(['telegram_logger.template' => 'telegram_logger::minimal']);
```

## Per-level overrides

Override root settings per PSR log level in `config/telegram_logger.php`:

```php
'levels' => [
    'error' => [
        'chat_id' => env('TELEGRAM_LOGGER_ERROR_CHAT_ID'),
        'message_thread_id' => env('TELEGRAM_LOGGER_ERROR_MESSAGE_THREAD_ID'),
        'template' => 'telegram_logger::minimal',
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

Unresolved keys fall back to the root config values.

## Forum topics / targeted sending

Route a single log message with the reserved `telegram` context key. That key is stripped before rendering, so it never appears in the delivered text.

### Numeric topic

```php
Log::info('Order paid', ['telegram' => ['topic' => 123]]);
```

### Named topic

Define optional named topics in config:

```php
'topics' => [
    'payments' => [
        'message_thread_id' => env('TELEGRAM_LOGGER_TOPIC_PAYMENTS'),
        // optional:
        // 'token' => ...,
        // 'chat_id' => ...,
        // 'template' => 'telegram_logger::minimal',
        // 'options' => ['disable_notification' => true],
        // 'duplicate' => true,
    ],
],
```

```php
Log::info('Order paid', ['telegram' => ['topic' => 'payments']]);
```

Supported keys per named topic (only `message_thread_id` is required):

- `message_thread_id` (required)
- `token`, `chat_id`, `template`, `options` (optional; fall back to **root** config)
- `duplicate` (optional, default `false`)

### Destination rules

1. No `telegram.topic` → send to the level-based destination (level overrides over root).
2. Topic without `duplicate` → send only to the topic destination.
3. Topic with `duplicate=true` → send to the level-based destination and the topic destination.

`duplicate` resolution:

- Context key `duplicate` wins when provided.
- For named topics, config `topics.*.duplicate` is used when context omits it.
- Numeric topics have no config default; without context `duplicate`, only the topic is used.

When a valid topic is applied, topic overrides fall back to **root** config (`token` / `chat_id` / `template` / `options`), not to level overrides. Level overrides apply only on the level-based pass.

Other behavior:

- Unknown or misconfigured named topic → error is written to the `single` log channel; the level-based destination is kept for that pass.
- Invalid `topic` type → fallback to the level-based destination only.
- Identical destinations (`chat_id` + `message_thread_id`) are deduplicated within one log write.

## Queue behavior

Messages are dispatched as `RaifuCore\TelegramLogger\Job`.

- `TELEGRAM_LOGGER_QUEUE_CONNECTION=sync` (default) — jobs run immediately in the same process.
- Async connections (`redis`, `database`, …) — jobs are pushed to the `TELEGRAM_LOGGER_QUEUE` queue (default `default`). Run a queue worker for that connection/queue.
- A failed HTTP request is not retried: the job is marked as failed immediately. On async connections check the `failed_jobs` table.

## Message behavior

- Default `parse_mode` is `html` (can be overridden via `options`).
- Rendered text is split into chunks of up to 4096 bytes, which keeps every chunk within Telegram's 4096-character message limit.
- If no `template` is configured, a plain fallback format is used: `<b>{app name}</b> ({level})` followed by the formatted message.
- Handler exceptions are written to `Log::channel('single')` and are not sent back to Telegram, so the logger cannot recurse into itself. The `single` channel must exist in your `config/logging.php` (it does in a default Laravel install).

## Testing

```bash
php artisan tg-logger:test
php artisan tg-logger:test --topic=payments
php artisan tg-logger:test --topic=123 --duplicate
```

`--topic` accepts a named topic from config or a numeric (non-negative) `message_thread_id`.  
`--duplicate` also sends the topic test message to the level-based destination.

The command sends one message per log level (8 in total) with a ~2 second pause between them to avoid Telegram rate limits, so it takes about 15 seconds to finish.

Reminder: the command uses the default logger, so `telegram` must be part of your default/`stack` channel.

## Troubleshooting


| Symptom                                             | What to check                                                            |
| --------------------------------------------------- | ------------------------------------------------------------------------ |
| Nothing is sent                                     | `TELEGRAM_LOGGER_ENABLE=true`                                            |
| `token or chat_id are not defined` in `single` logs | `TELEGRAM_LOGGER_BOT_TOKEN` / `TELEGRAM_LOGGER_CHAT_ID`                  |
| API / chat errors                                   | Bot is added to the chat and allowed to post (including the forum topic) |
| Wrong or missing topic                              | `message_thread_id` / named topic config                                 |
| Jobs never leave the queue                          | Queue worker is running for the configured connection and queue          |
| Test command silent                                 | `telegram` is included in the default/`stack` channel                    |


Package failures are logged to the Laravel `single` channel (`storage/logs/laravel.log` by default). This requires the `single` channel to be present in `config/logging.php` — it is there in a default Laravel install, so only check this if you have removed or renamed it.

## License

MIT