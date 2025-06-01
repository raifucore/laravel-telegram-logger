<?php

return [
    'enable' => env('TELEGRAM_LOGGER_ENABLE'),
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

    // Proxy. Ex.: tcp://host:port or tcp://user:pass@host:port
    'proxy' => null,

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
        'debug' => null,
        'info' => null,
        'notice' => null,
        'warning' => null,
        'error' => null,
        'critical' => null,
        'alert' => null,
        'emergency' => null,
    ],
];
