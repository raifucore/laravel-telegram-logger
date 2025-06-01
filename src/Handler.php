<?php

namespace RaifuCore\TelegramLogger;

use Exception;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;

class Handler extends AbstractProcessingHandler
{
    private array $config;
    private string $botToken;
    private int $chatId;
    private int|null $messageThreadId;
    private string $appName;
    private string $appEnv;

    public function __construct(array $config)
    {
        $level = Logger::toMonologLevel($config['level']);

        parent::__construct($level);

        // define variables for making Telegram request
        $this->config           = $config;
        $this->botToken         = $this->getConfigValue('token');
        $this->chatId           = $this->getConfigValue('chat_id');
        $this->messageThreadId  = $this->getConfigValue('message_thread_id');

        // define variables for text message
        $this->appName = config('app.name');
        $this->appEnv  = config('app.env');
    }

    public function write($record): void
    {
        if (!$this->botToken || !$this->chatId) {
            throw new \InvalidArgumentException('Bot token or chat id is not defined for Telegram logger');
        }

        // trying to make request and send notification
        try {
            $textChunks = str_split($this->formatText($record), 4096);

            foreach ($textChunks as $textChunk) {
                $this->sendMessage($textChunk);
            }
        } catch (Exception $exception) {
            \Log::channel('single')->error($exception->getMessage());
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter("%message% %context% %extra%\n", null, false, true);
    }

    private function formatText($record): string
    {
        if ($template = config('telegram_logger.template')) {
            if ($record instanceof LogRecord) {
                return view($template, array_merge($record->toArray(), [
                        'appName'   => $this->appName,
                        'appEnv'    => $this->appEnv,
                        'formatted' => $record->formatted,
                    ])
                )->render();
            }

            return view($template, array_merge($record, [
                    'appName' => $this->appName,
                    'appEnv'  => $this->appEnv,
                ])
            )->render();
        }

        return sprintf("<b>%s</b> (%s)\n%s", $this->appName, $record['level_name'], $record['formatted']);
    }

    private function sendMessage(string $text): void
    {
        $httpQuery = http_build_query(array_merge(
            [
                'text'               => $text,
                'chat_id'            => $this->chatId,
                'message_thread_id'  => $this->messageThreadId,
                'parse_mode'         => 'html',
            ],
            config('telegram-logger.options', [])
        ));

        $host = $this->getConfigValue('api_host');

        $url = $host . '/bot' . $this->botToken . '/sendMessage?' . $httpQuery;

        $proxy = $this->getConfigValue('proxy');

        if (!empty($proxy)) {
            $context = stream_context_create([
                'http' => [
                    'proxy' => $proxy,
                ],
            ]);
            file_get_contents($url, false, $context);
        } else {
            file_get_contents($url);
        }
    }

    private function getConfigValue(string $key): mixed
    {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }

        return config("telegram-logger.$key");
    }
}
