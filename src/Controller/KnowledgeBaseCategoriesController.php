<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseCategory;
use LeoT\FlarumAiSupport\Serializer\KnowledgeBaseCategorySerializer;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class KnowledgeBaseCategoriesController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = KnowledgeBaseCategorySerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        
        // 检查权限
        $actor->assertCan('leot-ai-support-widget.viewKnowledgeBase');
        
        // 获取所有分类
        $categories = KnowledgeBaseCategory::query()
            ->orderBy('name', 'asc')
            ->get();
            
        return $categories;
    }
}