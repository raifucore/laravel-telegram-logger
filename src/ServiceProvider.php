<?php

namespace RaifuCore\TelegramLogger;

use Illuminate\Support\ServiceProvider as CoreServiceProvider;

class ServiceProvider extends CoreServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/telegram_logger.php', 'telegram_logger');
    }

    public function boot(): void
    {
        $this->publishes([__DIR__ . '/../config/telegram_logger.php' => config_path('telegram_logger.php')], 'config');

        $this->_loadViews();
    }

    private function _loadViews(): void
    {
        $viewsPath = __DIR__ . '/../resources/views';

        // Load
        $this->loadViewsFrom($viewsPath, 'telegram_logger');

        // Publish
        $this->publishes([
            $viewsPath => resource_path('views/vendor/telegram_logger'),
        ], 'views');
    }
}
