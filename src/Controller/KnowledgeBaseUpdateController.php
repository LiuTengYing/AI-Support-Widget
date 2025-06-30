<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Api\Controller\AbstractShowController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;
use LeoT\FlarumAiSupport\Serializer\KnowledgeBaseEntrySerializer;

class KnowledgeBaseUpdateController extends AbstractShowController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = KnowledgeBaseEntrySerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        
        // 检查权限，只有管理员可以更新知识库条目
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
        
        // 获取请求体中的数据
        $data = $request->getParsedBody();
        $attributes = Arr::get($data, 'data.attributes', []);
        
        // 查找条目
        $entry = KnowledgeBaseEntry::findOrFail($id);
        
        // 更新条目属性
        if (isset($attributes['type'])) {
            $entry->type = $attributes['type'];
        }
        
        if (isset($attributes['question'])) {
            $entry->question = $attributes['question'];
        }
        
        if (isset($attributes['answer'])) {
            $entry->answer = $attributes['answer'];
        }
        
        if (isset($attributes['keywords'])) {
            $entry->keywords = $attributes['keywords'];
        }
        
        if (isset($attributes['category_id'])) {
            $entry->category_id = $attributes['category_id'] ?: null;
        }
        
        $entry->save();
        
        return $entry;
    }
} 