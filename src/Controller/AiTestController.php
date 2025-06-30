<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LeoT\FlarumAiSupport\Api\OpenAiService;
use LeoT\FlarumAiSupport\Api\DeepSeekService;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Api\Controller\AbstractShowController;
use Tobscure\JsonApi\Document;
use LeoT\FlarumAiSupport\Api\AiServiceInterface;
use Illuminate\Database\ConnectionInterface;

class AiTestController extends AbstractShowController
{
    protected $settings;
    protected $db;

    public function __construct(SettingsRepositoryInterface $settings, ConnectionInterface $db)
    {
        $this->settings = $settings;
        $this->db = $db;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        // Only allow administrators to access
        if (!$actor->isAdmin()) {
            return new JsonResponse(['error' => 'Admins only'], 403);
        }

        // 获取所有AI设置项
        $aiSettings = [
            'enabled' => $this->settings->get('leot-ai-support-widget.enabled'),
            'provider' => $this->settings->get('leot-ai-support-widget.provider'),
            'model_name' => $this->settings->get('leot-ai-support-widget.model_name'),
            'max_tokens' => $this->settings->get('leot-ai-support-widget.max_tokens'),
            'timeout' => $this->settings->get('leot-ai-support-widget.timeout'),
            'daily_requests_limit' => $this->settings->get('leot-ai-support-widget.daily_requests_limit'),
            'enable_indexing' => $this->settings->get('leot-ai-support-widget.enable_indexing'),
            'search_limit' => $this->settings->get('leot-ai-support-widget.search_limit'),
            'widget_position' => $this->settings->get('leot-ai-support-widget.widget_position'),
            'theme' => $this->settings->get('leot-ai-support-widget.theme'),
        ];
        
        // 检查数据库中ai_support_usage表是否存在
        $hasTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage');
        
        $result = [
            'ai_settings' => $aiSettings,
            'ai_support_usage_table_exists' => $hasTable,
        ];
        
        // 尝试获取所有用户的使用情况
        if ($hasTable) {
            $usageRecords = $this->db->table('ai_support_usage')
                ->orderBy('date', 'desc')
                ->limit(20)
                ->get();
            
            $result['usage_records'] = $usageRecords;
            }

        return new JsonResponse($result);
    }
}