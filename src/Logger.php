<?php

namespace RaifuCore\TelegramLogger;

class Logger
{
    public function __invoke(array $config): \Monolog\Logger
    {
        return new \Monolog\Logger(
            config('app.name'),
            [
                new Handler($config),
            ]
        );
    }
}