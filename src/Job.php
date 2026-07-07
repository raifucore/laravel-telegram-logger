<?php

namespace RaifuCore\TelegramLogger;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $retryAfter = 60;

    public function __construct(
        private string      $url,
        private array       $payload,
        private string|null $proxy = null,
        private int         $timeout = 5,
    )
    {
    }

    public function handle(): void
    {
        $httpClientOption = [
            RequestOptions::VERIFY => false,
            RequestOptions::TIMEOUT => $this->timeout,
        ];

        if (!is_null($this->proxy)) {
            $httpClientOption[RequestOptions::PROXY] = $this->proxy;
        }

        $requestOptions = [
            RequestOptions::FORM_PARAMS => $this->payload,
        ];

        $httpClient = new Client($httpClientOption);

        try {
            $httpClient->post($this->url, $requestOptions);
        } catch (\Throwable $exception) {
            $this->fail($exception);
        }
    }
}
