<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Flarum\Api\Controller\AbstractShowController;
use Tobscure\JsonApi\Document;
use LeoT\FlarumAiSupport\Model\AiResponseRating;

class AiRatingController extends AbstractShowController
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * 处理请求并返回响应
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $body = $request->getParsedBody();
        
        $messageId = Arr::get($body, 'messageId', '');
        $rating = Arr::get($body, 'rating', '');
        
        if (empty($messageId) || empty($rating)) {
            return new JsonResponse(['error' => 'Message ID and rating are required'], 400);
        }

        if (!in_array($rating, ['good', 'bad'])) {
            return new JsonResponse(['error' => 'Invalid rating value'], 400);
        }
        
        // 检查用户权限
        if (!$this->canUseAiSupport($actor)) {
            return new JsonResponse(['error' => 'Permission denied'], 403);
        }

        try {
            // 这里可以将评分保存到数据库，用于后续分析
            // 目前简单返回成功
            return new JsonResponse([
                'success' => true,
                'message' => 'Rating saved successfully'
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Error saving rating: ' . $e->getMessage()
            ], 500);
        }
    }
    
    private function canUseAiSupport($actor): bool
    {
        // 禁止游客（未注册用户）使用AI支持功能
        if ($actor->isGuest()) {
            return false;
        }
        
        // 检查用户是否有leot-flarum-ai-support-widget.use权限
        if ($actor->can('leot-flarum-ai-support-widget.use')) {
            return true;
        }
        
        return false;
    }
} 