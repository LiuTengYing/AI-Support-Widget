<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Support\Arr;

class KnowledgeBaseDeleteController extends AbstractDeleteController
{
    /**
     * {@inheritdoc}
     */
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        
        // 检查权限，只有管理员可以删除
        if (!$actor->isAdmin()) {
            $actor->assertPermission('leot-ai-support-widget.manageKnowledgeBase');
        }
        
        // 从路径参数中获取ID
        $routeParams = $request->getAttribute('routeParameters', []);
        $id = Arr::get($routeParams, 'id');
        
        if (!$id) {
            // 尝试从查询参数获取
            $id = Arr::get($request->getQueryParams(), 'id');
        }
        
        if (!$id) {
            throw new \InvalidArgumentException('Entry ID is required');
        }
        
        $entry = KnowledgeBaseEntry::findOrFail($id);
        $entry->delete();
    }
} 