<?php

namespace RaifuCore\TelegramLogger;

use Illuminate\Support\Facades\Log;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Throwable;

class Handler extends AbstractProcessingHandler
{
    const TELEGRAM_API_HOST = 'https://api.telegram.org';

    private array $config = [];
    private bool $isEnable = false;

    private string|null $token = null;
    private int|null $chatId = null;
    private int|null $messageThreadId = null;
    private string|null $template = null;
    private array $options = [];

    public function __construct(array $config)
    {
        parent::__construct($config['level']);

        $this->config = config('telegram_logger');

        $this->isEnable = (bool)$this->config['enable'] ?? null;
    }

    private function _determineParams(LogRecord|array $record): void
    {
        $level = $record instanceof LogRecord
            ? $record->level
            : ($record['level'] ?? Level::Debug);

        $this->token = $this->config['levels'][$level->toPsrLogLevel()]['token']
            ?? $this->config['token'];

        $this->chatId = $this->config['levels'][$level->toPsrLogLevel()]['chat_id']
            ?? $this->config['chat_id'];

        $this->messageThreadId = $this->config['levels'][$level->toPsrLogLevel()]['message_thread_id']
            ?? $this->config['message_thread_id'];

        $this->template = $this->config['levels'][$level->toPsrLogLevel()]['template']
            ?? $this->config['template'];

        $this->options = array_merge(
            ['parse_mode' => 'html'],
            $this->config['options'] ?? [],
            $this->config['levels'][$level->toPsrLogLevel()]['options'] ?? []
        );

        if (!$this->token || !$this->chatId) {
            throw new \InvalidArgumentException('token and chat_id are not defined');
        }
    }

    public function write(LogRecord|array $record): void
    {
        if (!$this->isEnable) {
            return;
        }

        $this->_determineParams($record);

        try {
            $textChunks = str_split($this->formatText($record), 4096);

            foreach ($textChunks as $textChunk) {
                $this->sendMessage($textChunk);
            }
        } catch (Throwable $e) {
            Log::channel('single')->error($e->getMessage());
        }
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter("%message% %context% %extra%\n", null, false, true);
    }

    private function formatText($record): string
    {
        $appName = config('app.name');
        $appEnv = config('app.env');

        if ($this->template) {
            if ($record instanceof LogRecord) {
                return view($this->template, array_merge($record->toArray(), [
                        'appName' => $appName,
                        'appEnv' => $appEnv,
                        'formatted' => $record->formatted,
                    ])
                )->render();
            }

            return view($this->template, array_merge($record, [
                    'appName' => $appName,
                    'appEnv' => $appEnv,
                ])
            )->render();
        }

        return sprintf("<b>%s</b> (%s)\n%s", $appName, $record['level_name'], $record['formatted']);
    }

    private function sendMessage(string $text): void
    {
        $httpQuery = http_build_query(array_merge(
            [
                'text' => $text,
                'chat_id' => $this->chatId,
                'message_thread_id' => $this->messageThreadId,
                'parse_mode' => 'html',
            ],
            $this->options
        ));

        $url = self::TELEGRAM_API_HOST . '/bot' . $this->token . '/sendMessage?' . $httpQuery;

        $proxy = $this->config['proxy'] ?? null;

        if ($proxy) {
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
}
