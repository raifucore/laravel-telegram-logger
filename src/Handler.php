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

        $this->isEnable = (bool) ($this->config['enable'] ?? null);
    }

    private function _determineParams(LogRecord|array $record, string|int|null $topic = null): void
    {
        $level = $record instanceof LogRecord
            ? $record->level
            : ($record['level'] ?? Level::Debug);

        $psrLogLevel = $level->toPsrLogLevel();

        $this->token = $this->config['levels'][$psrLogLevel]['token']
            ?? $this->config['token'];

        $this->chatId = $this->config['levels'][$psrLogLevel]['chat_id']
            ?? $this->config['chat_id'];

        $this->messageThreadId = $this->config['levels'][$psrLogLevel]['message_thread_id']
            ?? $this->config['message_thread_id'];

        $this->template = $this->config['levels'][$psrLogLevel]['template']
            ?? $this->config['template'];

        $this->options = array_merge(
            ['parse_mode' => 'html'],
            $this->config['options'] ?? [],
            $this->config['levels'][$psrLogLevel]['options'] ?? []
        );

        if ($topic !== null) {
            $this->_applyTopicOverrides($topic);
        }

        if (!$this->token || !$this->chatId) {
            throw new \InvalidArgumentException('token or chat_id are not defined');
        }
    }

    /**
     * An int topic is used as a raw message_thread_id, a string topic is
     * looked up in the optional 'topics' config section. On an unknown or
     * misconfigured topic the level-based params determined above are kept.
     */
    private function _applyTopicOverrides(string|int $topic): void
    {
        $topicConfig = is_int($topic)
            ? ['message_thread_id' => $topic]
            : ($this->config['topics'][$topic] ?? []);

        if (empty($topicConfig['message_thread_id'])) {
            Log::channel('single')->error(sprintf('Telegram logger: unknown or misconfigured topic "%s"', $topic));

            return;
        }

        $this->token = $topicConfig['token'] ?? $this->config['token'];
        $this->chatId = $topicConfig['chat_id'] ?? $this->config['chat_id'];
        $this->messageThreadId = (int) $topicConfig['message_thread_id'];
        $this->template = $topicConfig['template'] ?? $this->config['template'];

        $this->options = array_merge(
            ['parse_mode' => 'html'],
            $this->config['options'] ?? [],
            $topicConfig['options'] ?? []
        );
    }

    public function write(LogRecord|array $record): void
    {
        if (!$this->isEnable) {
            return;
        }

        try {

            $telegramContext = $this->_pullTelegramContext($record);

            $sentDestinations = [];

            foreach ($this->_determinePasses($telegramContext) as $topic) {
                $this->_determineParams($record, $topic);

                // Skip if a previous pass already sent to the same destination
                $destinationKey = $this->chatId . ':' . $this->messageThreadId;

                if (isset($sentDestinations[$destinationKey])) {
                    continue;
                }

                $sentDestinations[$destinationKey] = true;

                $textChunks = str_split($this->formatText($record), 4096);

                foreach ($textChunks as $textChunk) {
                    $this->sendMessage($textChunk);
                }
            }
        } catch (Throwable $e) {
            Log::channel('single')->error($e->getMessage());
        }
    }

    /**
     * A pass is a single _determineParams() + send cycle: null for the
     * regular level-based destination, string|int for a topic-targeted one.
     */
    private function _determinePasses(array $telegramContext): array
    {
        $topic = $telegramContext['topic'] ?? null;

        if ($topic === null) {
            return [null];
        }

        if (!is_string($topic) && !is_int($topic)) {
            Log::channel('single')->error(sprintf(
                'Telegram logger: invalid topic type "%s", falling back to the level-based destination',
                get_debug_type($topic)
            ));

            return [null];
        }

        $duplicate = (bool) ($telegramContext['duplicate']
            ?? (is_string($topic) ? ($this->config['topics'][$topic]['duplicate'] ?? false) : false));

        return $duplicate ? [null, $topic] : [$topic];
    }

    /**
     * Extracts the reserved 'telegram' key from the record context and strips
     * it from the record so it doesn't leak into the rendered message.
     */
    private function _pullTelegramContext(LogRecord|array &$record): array
    {
        $context = $record instanceof LogRecord
            ? $record->context
            : ($record['context'] ?? []);

        if (!array_key_exists('telegram', $context)) {
            return [];
        }

        $telegramContext = $context['telegram'];

        unset($context['telegram']);

        if ($record instanceof LogRecord) {
            $record = $record->with(context: $context);
            $record->formatted = $this->getFormatter()->format($record);
        } else {
            $record['context'] = $context;
        }

        return is_array($telegramContext) ? $telegramContext : [];
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter("%message% %context% %extra%\n", null, true, true);
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
        $payload = array_merge(
            [
                'text' => $text,
                'chat_id' => $this->chatId,
            ],
            $this->options
        );

        if ($this->messageThreadId !== null) {
            $payload['message_thread_id'] = $this->messageThreadId;
        }

        $url = self::TELEGRAM_API_HOST . '/bot' . $this->token . '/sendMessage';

        $proxy = $this->config['proxy'] ?? null;
        $timeout = (int)($this->config['timeout'] ?? 5);

        $job = Job::dispatch($url, $payload, $proxy, $timeout);

        if (!empty($this->config['queue'])) {
            $job->onQueue($this->config['queue']);
        }

        if (!empty($this->config['connection'])) {
            $job->onConnection($this->config['connection']);
        }
    }
}
