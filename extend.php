<?php

/**
 * LeoT AI Support Widget Extension for Flarum.
 *
 * 为Flarum论坛添加一个AI支持小部件
 */

namespace LeoT\FlarumAiSupport;

use Flarum\Extend;
use Flarum\Api\Serializer\ForumSerializer;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Flarum\Settings\SettingsRepositoryInterface;
use LeoT\FlarumAiSupport\Services\AiProviderService;
use LeoT\FlarumAiSupport\Service\ForumSearchService;
use LeoT\FlarumAiSupport\Service\KnowledgeBaseSearchService;
use LeoT\FlarumAiSupport\Api\DeepSeekService;
use LeoT\FlarumAiSupport\Api\OpenAiService;
use LeoT\FlarumAiSupport\Middleware\CheckAiPermission;
use LeoT\FlarumAiSupport\Middleware\LogRequestMiddleware;
use LeoT\FlarumAiSupport\Controller\AiChatController;
use LeoT\FlarumAiSupport\Controller\AiTestController;
use LeoT\FlarumAiSupport\Controller\ApiTestController;
use LeoT\FlarumAiSupport\Api\Controller\AiUsageStatsController;
use LeoT\FlarumAiSupport\Controller\AiRatingController;
use LeoT\FlarumAiSupport\Controller\KnowledgeBaseDeleteController;
use LeoT\FlarumAiSupport\Controller\KnowledgeBaseUpdateController;
use LeoT\FlarumAiSupport\Controller\KnowledgeBaseCategoriesController;
use LeoT\FlarumAiSupport\Controller\KnowledgeBaseController;
use LeoT\FlarumAiSupport\Controller\KnowledgeBaseCreateController;
use Psr\Http\Server\RequestHandlerInterface;
use Flarum\Http\RequestUtil;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseCategory;
use LeoT\FlarumAiSupport\Provider\AiServiceProvider;
use LeoT\FlarumAiSupport\Service\KnowledgeBaseServiceProvider;
use LeoT\FlarumAiSupport\Access\UserPolicy; // 添加这行导入

return [
    // 注册命名空间
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js')
        ->css(__DIR__ . '/less/forum.less')
        ->route('/ai-support', 'ai-support'),

    // 管理面板
    (new Extend\Frontend('admin'))
        ->js(__DIR__ . '/js/dist/admin.js')
        ->css(__DIR__ . '/less/admin.less'),

    // 本地化
    new Extend\Locales(__DIR__ . '/locale'),

    // 注册中间件
    (new Extend\Middleware('api'))
        ->add(CheckAiPermission::class),
    
    // 明确为AI聊天API注册中间件（直接指定路由）
    (new Extend\Middleware('api'))
        ->add(LogRequestMiddleware::class),
    
    // 使用Controller目录中的控制器类处理API请求
    (new Extend\Routes('api'))
        ->post('/ai-support/chat', 'ai-support.chat', AiChatController::class)
        ->get('/ai-support/test', 'ai-support.test', AiTestController::class)
        ->get('/ai-api-test', 'ai-api-test', ApiTestController::class)
        ->get('/ai-support/stats', 'ai-support.stats', AiUsageStatsController::class)
        ->post('/ai-support/rating', 'ai-support.rating', AiRatingController::class)
        // 知识库API路由 - 注意：先定义具体路径，再定义通用路径
        ->delete('/ai-support/kb/{id}', 'ai-support.kb.delete', KnowledgeBaseDeleteController::class)
        ->patch('/ai-support/kb/{id}', 'ai-support.kb.update', KnowledgeBaseUpdateController::class)
        ->get('/ai-support/kb/categories', 'ai-support.kb.categories', KnowledgeBaseCategoriesController::class)
        ->get('/ai-support/kb', 'ai-support.kb.index', KnowledgeBaseController::class)
        ->post('/ai-support/kb', 'ai-support.kb.create', KnowledgeBaseCreateController::class),
    
    // 权限
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function (ForumSerializer $serializer) {
            $actor = $serializer->getActor();
            $settings = resolve('flarum.settings');
            
            return [
                'canUseAiSupport' => $actor->can('leot-ai-support-widget.use'),
                'aiSupportEnabled' => (bool) $settings->get('leot-ai-support-widget.enabled', true), // 默认启用
                // 添加调试信息
                'aiSupportDebug' => [
                    'extensionEnabled' => true,
                    'actorId' => $actor->id,
                    'actorIsGuest' => $actor->isGuest(),
                    'settingValue' => $settings->get('leot-ai-support-widget.enabled')
                ]
            ];
        }),
    
    // 注册权限 - 修复命名空间
    (new Extend\Policy())
        ->modelPolicy(User::class, UserPolicy::class),
    
    // 注册扩展设置，并设置默认值
    (new Extend\Settings())
        ->serializeToForum('leot-ai-support-widget.enabled', 'leot-ai-support-widget.enabled', 'boolval', true)
        ->serializeToForum('leot-ai-support-widget.widget_position', 'leot-ai-support-widget.widget_position', null, 'bottom-right')
        ->serializeToForum('leot-ai-support-widget.theme', 'leot-ai-support-widget.theme', null, 'auto')
        ->default('leot-ai-support-widget.enabled', true)
        ->default('leot-ai-support-widget.provider', 'openai')
        ->default('leot-ai-support-widget.model_name', 'gpt-3.5-turbo')
        ->default('leot-ai-support-widget.daily_requests_limit', 20)
        // 知识库相关设置
        ->default('leot-ai-support-widget.kb_enabled', true)
        ->default('leot-ai-support-widget.kb_search_weight', 1.5), // 知识库搜索权重
        
    // 注册模型
    (new Extend\Model(KnowledgeBaseEntry::class)),
    (new Extend\Model(KnowledgeBaseCategory::class)),
    
    // 注册服务到容器 - 修复命名空间
    (new Extend\ServiceProvider())
        ->register(KnowledgeBaseServiceProvider::class)
        ->register(AiServiceProvider::class),
];