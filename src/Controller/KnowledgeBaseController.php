<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;
use LeoT\FlarumAiSupport\Serializer\KnowledgeBaseEntrySerializer;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class KnowledgeBaseController extends AbstractListController
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
        
        // 检查权限
        $actor->assertCan('leot-ai-support-widget.viewKnowledgeBase');
        
        $queryParams = $request->getQueryParams();
        $categoryId = isset($queryParams['category_id']) ? (int) $queryParams['category_id'] : null;
        $type = isset($queryParams['type']) ? $queryParams['type'] : null;
        $query = isset($queryParams['q']) ? $queryParams['q'] : null;
        
        // 构建查询
        $kbQuery = KnowledgeBaseEntry::query();
        
        if ($categoryId) {
            $kbQuery->where('category_id', $categoryId);
        }
        
        if ($type) {
            $kbQuery->where('type', $type);
        }
        
        if ($query) {
            $kbQuery->where(function ($q) use ($query) {
                $q->where('question', 'like', "%{$query}%")
                  ->orWhere('answer', 'like', "%{$query}%")
                  ->orWhere('keywords', 'like', "%{$query}%");
            });
        }
        
        // 排序和分页
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        
        $entries = $kbQuery
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
            
        return $entries;
    }
} 