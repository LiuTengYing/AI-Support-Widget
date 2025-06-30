<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use LeoT\FlarumAiSupport\Serializer\KnowledgeBaseEntrySerializer;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;

class KnowledgeBaseCreateController extends AbstractCreateController
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
        
        // 检查权限，只有管理员可以创建知识库条目
        if (!$actor->isAdmin()) {
            $actor->assertPermission('leot-ai-support-widget.manageKnowledgeBase');
        }
        
        // 获取请求体中的数据
        $data = $request->getParsedBody();
        $attributes = Arr::get($data, 'data.attributes', []);
        
        // 创建新条目
        $entry = new KnowledgeBaseEntry();
        
        // 设置条目属性
        $entry->type = Arr::get($attributes, 'type', 'qa');
        $entry->question = Arr::get($attributes, 'question');
        $entry->answer = Arr::get($attributes, 'answer');
        $entry->keywords = Arr::get($attributes, 'keywords');
        $entry->category_id = Arr::get($attributes, 'category_id');
        
        $entry->save();
        
        return $entry;
    }
} 