<?php

namespace LeoT\FlarumAiSupport\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use LeoT\FlarumAiSupport\Services\AiProviderService;
use LeoT\FlarumAiSupport\Api\OpenAiService;
use LeoT\FlarumAiSupport\Api\DeepSeekService;

class AiServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        // 注册AiProviderService为单例
        $this->container->singleton(AiProviderService::class, function (Container $container) {
            return new AiProviderService(
                $container->make(SettingsRepositoryInterface::class),
                $container
            );
        });
        
        // 注册OpenAiService为单例
        $this->container->singleton(OpenAiService::class, function (Container $container) {
            return new OpenAiService(
                $container->make(SettingsRepositoryInterface::class)
            );
        });
        
        // 注册DeepSeekService为单例
        $this->container->singleton(DeepSeekService::class, function (Container $container) {
            return new DeepSeekService(
                $container->make(SettingsRepositoryInterface::class)
            );
        });
    }
}