<?php

namespace LeoT\FlarumAiSupport\Middleware;

use Flarum\User\User;
use Flarum\Settings\SettingsRepositoryInterface;
use LeoT\FlarumAiSupport\Services\UsageCounterService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Illuminate\Database\ConnectionInterface;
use Carbon\Carbon;

class CheckAiPermission implements MiddlewareInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    
    /**
     * @var UsageCounterService
     */
    protected $counter;
    
    /**
     * @var ConnectionInterface
     */
    protected $db;
    
    /**
     * @param SettingsRepositoryInterface $settings
     * @param UsageCounterService $counter
     * @param ConnectionInterface $db
     */
    public function __construct(SettingsRepositoryInterface $settings, UsageCounterService $counter, ConnectionInterface $db)
    {
        $this->settings = $settings;
        $this->counter = $counter;
        $this->db = $db;
    }
    
    /**
     * 处理请求
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 只处理AI聊天相关的请求
        $path = $request->getUri()->getPath();
        if (strpos($path, '/api/ai-support/chat') === false) {
            return $handler->handle($request);
        }
        
        // 检查是否启用AI小部件
        if ($this->settings->get('leot-ai-support-widget.enabled') !== '1') {
            $response = new JsonResponse([
                'errors' => [
                    [
                        'status' => '403',
                        'code' => 'ai_support_disabled',
                        'title' => 'AI Support Disabled',
                        'detail' => 'The AI support feature is currently disabled.'
                    ]
                ]
            ], 403);
            
            return $response;
        }
        
        /** @var User $actor */
        $actor = $request->getAttribute('actor');
        
        // 检查用户权限
        if (!$actor->hasPermission('leot-ai-support-widget.use')) {
            $response = new JsonResponse([
                'errors' => [
                    [
                        'status' => '403',
                        'code' => 'permission_denied',
                        'title' => 'Permission Denied',
                        'detail' => 'You do not have permission to use AI Support.'
                    ]
                ]
            ], 403);
            
            return $response;
        }
        
        // 跳过对管理员的限制
        if ($actor->isAdmin()) {
            return $handler->handle($request);
        }
        
        // 手动获取每日请求限制，确保正确读取
        $dailyLimit = 1; // 默认强制为1
        try {
            $dbSetting = $this->db->table('settings')
                ->where('key', 'leot-ai-support-widget.daily_requests_limit')
                ->first();
            
            if ($dbSetting && isset($dbSetting->value)) {
                $dailyLimit = (int)$dbSetting->value;
                if ($dailyLimit < 1) {
                    $dailyLimit = 1; // 确保最小值为1
                }
            }
        } catch (\Exception $e) {
            error_log('[AI Support] Error getting daily limit from DB: ' . $e->getMessage());
        }
        
        // 检查今日使用次数
        $date = date('Y-m-d');
        $todayCount = 0;
        $tableExists = false;
        $record = null;
        
        try {
            // 直接从数据库检查使用情况
            $tableExists = $this->db->getSchemaBuilder()->hasTable('ai_support_usage');
            
            if ($tableExists) {
                $record = $this->db->table('ai_support_usage')
                    ->where('user_id', $actor->id)
                    ->where('date', $date)
                    ->first();
                
                $todayCount = $record ? $record->count : 0;
            } else {
                // 如果表不存在，尝试创建
                $this->createUsageTable();
                $tableExists = true;
            }
        } catch (\Exception $e) {
            error_log('[AI Support] Error checking usage: ' . $e->getMessage());
        }
        
        // 检查用户是否已达到限制
        if ($todayCount >= $dailyLimit) {
            $response = new JsonResponse([
                'errors' => [
                    [
                        'status' => '429',
                        'code' => 'daily_limit_exceeded',
                        'title' => 'Daily Limit Exceeded',
                        'detail' => '您已达到今日使用上限，请明天再试。'
                    ]
                ]
            ], 429);
            
            return $response;
        }
        
        // 增加使用计数
        try {
            if ($tableExists) {
                $now = Carbon::now();
                if ($record) {
                    // 更新现有记录
                    $this->db->table('ai_support_usage')
                        ->where('id', $record->id)
                        ->update([
                            'count' => $record->count + 1,
                            'updated_at' => $now
                        ]);
                } else {
                    // 创建新记录
                    $this->db->table('ai_support_usage')
                        ->insert([
                            'user_id' => $actor->id,
                            'date' => $date,
                            'count' => 1,
                            'created_at' => $now,
                            'updated_at' => $now
                        ]);
                }
            }
        } catch (\Exception $e) {
            error_log('[AI Support] Error incrementing count: ' . $e->getMessage());
        }
        
        return $handler->handle($request);
    }
    
    /**
     * 创建使用记录表
     */
    private function createUsageTable()
    {
        try {
            $this->db->getSchemaBuilder()->create('ai_support_usage', function ($table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->date('date');
                $table->unsignedInteger('count')->default(0);
                $table->timestamps();
                
                $table->index(['user_id', 'date']);
            });
            
            return true;
        } catch (\Exception $e) {
            error_log('[AI Support] Error creating table: ' . $e->getMessage());
            return false;
        }
    }
} 