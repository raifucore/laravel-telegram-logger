<?php

namespace RaifuCore\TelegramLogger;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{
    protected $signature = 'tg-logger:test
                            {--topic= : Named topic from config or numeric message_thread_id to send an extra test message to}
                            {--duplicate : Also send the topic test message to the level-based destination}';

    protected $description = 'Send test log messages using the current logger configuration';

    public function handle(): void
    {
        Log::debug("debug\nnew line");
        sleep(2);
        Log::info("info\nnew line");
        sleep(2);
        Log::notice("notice\nnew line");
        sleep(2);
        Log::warning("warning\nnew line");
        sleep(2);
        Log::error("error\nnew line");
        sleep(2);
        Log::critical("critical\nnew line");
        sleep(2);
        Log::alert("alert\nnew line");
        sleep(2);
        Log::emergency("emergency\nnew line");

        $this->_testTopic();
    }

    private function _testTopic(): void
    {
        $topic = $this->option('topic');

        if ($topic === null) {
            return;
        }

        $telegram = [
            'topic' => ctype_digit($topic) ? (int) $topic : $topic,
        ];

        if ($this->option('duplicate')) {
            $telegram['duplicate'] = true;
        }

        sleep(2);
        Log::info("topic\nnew line", ['telegram' => $telegram]);
    }
}
