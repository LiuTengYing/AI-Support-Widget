<?php

namespace LeoT\FlarumAiSupport\Service;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Http\UrlGenerator;
use LeoT\FlarumAiSupport\Service\KnowledgeBaseSearchService;

class KnowledgeBaseServiceProvider extends AbstractServiceProvider
{
    public function register()
    {
        $this->container->singleton(KnowledgeBaseSearchService::class, function ($container) {
            return new KnowledgeBaseSearchService(
                $container->make('flarum.settings'),
                $container->make(UrlGenerator::class)
            );
        });
    }
} 