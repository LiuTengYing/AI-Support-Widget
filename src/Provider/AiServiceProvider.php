<?php

namespace LeoT\FlarumAiSupport\Provider;

use Flarum\Foundation\AbstractServiceProvider;
use LeoT\FlarumAiSupport\Api\AiServiceInterface;
use LeoT\FlarumAiSupport\Api\OpenAiService;
use LeoT\FlarumAiSupport\Api\DeepSeekService;

class AiServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(AiServiceInterface::class, function ($container) {
            $settings = $container->make('flarum.settings');
            $provider = $settings->get('ai-support.provider', 'openai');
            
            if ($provider === 'deepseek') {
                return $container->make(DeepSeekService::class);
            }
            
            // 默认使用OpenAI
            return $container->make(OpenAiService::class);
        });
    }
}