<?php

namespace RaifuCore\TelegramLogger;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{
    protected $signature = 'tg-logger:test';

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
    }
}
