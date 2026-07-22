<?php

return [
    'enable' => env('TELEGRAM_LOGGER_ENABLE', false),
    'token' => env('TELEGRAM_LOGGER_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_LOGGER_CHAT_ID'),
    'message_thread_id' => env('TELEGRAM_LOGGER_MESSAGE_THREAD_ID'),

    // Blade Template to use formatting logs
    'template' => 'telegram_logger::standard',

    // Telegram sendMessage options: https://core.telegram.org/bots/api#sendmessage
    'options' => [
        // 'parse_mode' => 'html',
        // 'disable_web_page_preview' => true,
        // 'disable_notification' => false
    ],

    // Proxy. Ex.: tcp://host:port or tcp://user:pass@host:port or socks5://user:pass@host:port
    'proxy' => env('TELEGRAM_LOGGER_PROXY'),

    // Queue settings for telegram logger jobs
    'queue' => env('TELEGRAM_LOGGER_QUEUE', 'default'),
    'connection' => env('TELEGRAM_LOGGER_QUEUE_CONNECTION', 'sync'),

    // HTTP timeout (seconds) for Telegram API requests
    'timeout' => (int) env('TELEGRAM_LOGGER_TIMEOUT', 5),

    'levels' => [
        /**
         * You can specify settings for a specific level, otherwise the default values will be used
         * 'debug' => [
         *      'token' => env('TELEGRAM_LOGGER_DEBUG_BOT_TOKEN'),
         *      'chat_id' => env('TELEGRAM_LOGGER_DEBUG_CHAT_ID'),
         *      'message_thread_id' => env('TELEGRAM_LOGGER_DEBUG_MESSAGE_THREAD_ID'),
         * ],
         * 'info' => [
         *      'message_thread_id' => env('TELEGRAM_LOGGER_INFO_MESSAGE_THREAD_ID'),
         *      'template' => 'telegram_logger::info',
         * ],
         * 'error' => [
         *      'options' => [
         *           'disable_notification' => true
         *      ],
         * ],
         *
         */
        'debug' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_DEBUG_MESSAGE_THREAD_ID'),
        ],
        'info' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_INFO_MESSAGE_THREAD_ID'),
        ],
        'notice' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_NOTICE_MESSAGE_THREAD_ID'),
        ],
        'warning' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_WARNING_MESSAGE_THREAD_ID'),
        ],
        'error' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_ERROR_MESSAGE_THREAD_ID'),
        ],
        'critical' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_CRITICAL_MESSAGE_THREAD_ID'),
        ],
        'alert' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_ALERT_MESSAGE_THREAD_ID'),
        ],
        'emergency' => [
            'message_thread_id' => env('TELEGRAM_LOGGER_EMERGENCY_MESSAGE_THREAD_ID'),
        ],
    ],

    // Named topics for targeted sending: Log::info('msg', ['telegram' => ['topic' => 'payments']])
    // This section is fully optional.
    'topics' => [
        /**
         * You can define named topics with the same override keys as 'levels'.
         * Only 'message_thread_id' is required, all other keys are optional:
         * - 'token' (optional, defaults to the root 'token')
         * - 'chat_id' (optional, defaults to the root 'chat_id')
         * - 'template' (optional, defaults to the root 'template')
         * - 'options' (optional, merged over the root 'options')
         * - 'duplicate' (optional, defaults to false) - when true, the message
         *   is also sent to the level-based destination.
         *
         * 'payments' => [
         *      'message_thread_id' => env('TELEGRAM_LOGGER_TOPIC_PAYMENTS'),
         * ],
         */
    ],
];
