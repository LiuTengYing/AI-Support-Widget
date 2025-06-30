<?php

namespace LeoT\FlarumAiSupport\Service;

use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;
use Illuminate\Database\Eloquent\Builder;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Http\UrlGenerator;

/**
 * 知识库搜索服务
 */
class KnowledgeBaseSearchService
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    
    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * 构造函数
     *
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->settings = $settings;
        $this->url = $url;
    }

    /**
     * 搜索知识库
     *
     * @param string $query 搜索关键词
     * @param int $limit 返回结果数量限制
     * @return array 搜索结果
     */
    public function search(string $query, int $limit = 5): array
    {
        if (empty($query) || strlen($query) < 2) {
            return [];
        }

        // 分词处理
        $keywords = $this->extractKeywords($query);
        
        if (empty($keywords)) {
            return [];
        }

        // 构建查询
        $entries = KnowledgeBaseEntry::query()
            ->where(function (Builder $builder) use ($keywords, $query) {
                foreach ($keywords as $keyword) {
                    $builder->orWhere('question', 'like', "%{$keyword}%")
                           ->orWhere('answer', 'like', "%{$keyword}%")
                           ->orWhere('keywords', 'like', "%{$keyword}%");
                }
                // 添加对完整查询的搜索，提高精确匹配的权重
                $builder->orWhere('question', 'like', "%{$query}%")
                       ->orWhere('answer', 'like', "%{$query}%")
                       ->orWhere('keywords', 'like', "%{$query}%");
            })
            ->limit($limit)
            ->get();

        // 格式化结果
        return $entries->map(function ($entry) use ($query) {
            // 为了与AiProviderService中的格式兼容
            $title = $entry->question;
            $type = $entry->type;
            
            // 如果是Content类型且没有标题，从内容中生成一个标题
            if ($type === 'content' && empty($title)) {
                // 从答案/内容中提取前30个字符作为标题
                $title = mb_substr($entry->answer, 0, 30);
                if (mb_strlen($entry->answer) > 30) {
                    $title .= '...';
                }
                
                // 如果有关键词，使用第一个关键词作为标题的一部分
                if (!empty($entry->keywords)) {
                    $keywordsArray = explode(',', $entry->keywords);
                    if (!empty($keywordsArray[0])) {
                        $title = trim($keywordsArray[0]) . ': ' . $title;
                    }
                }
            }
            
            // 计算相关度
            $relevance = $this->calculateRelevance($query, $entry);
            
            return [
                'id' => $entry->id,
                'type' => $type,
                'title' => $title ?: 'Knowledge Base Entry', // 确保始终有标题
                'content' => $entry->answer,
                'question' => $entry->question,
                'answer' => $entry->answer,
                'source' => 'knowledge_base',
                'category' => $entry->category ? $entry->category->name : null,
                'keywords' => $entry->keywords,
                'relevance' => $relevance,
            ];
        })->toArray();
    }

    /**
     * 计算条目与查询的相关度
     * 
     * @param string $query 用户查询
     * @param KnowledgeBaseEntry $entry 知识库条目
     * @return float 相关度评分 (0-1)
     */
    protected function calculateRelevance(string $query, $entry): float
    {
        $query = mb_strtolower($query);
        $relevance = 0;
        
        // 检查标题匹配度
        if (!empty($entry->question)) {
            $titleLower = mb_strtolower($entry->question);
            if (mb_strpos($titleLower, $query) !== false) {
                $relevance += 0.6; // 标题完全包含查询，高相关度
            } else {
                // 检查查询中的关键词是否在标题中
                $keywords = $this->extractKeywords($query);
                $matchCount = 0;
                foreach ($keywords as $keyword) {
                    if (mb_strpos($titleLower, mb_strtolower($keyword)) !== false) {
                        $matchCount++;
                    }
                }
                if (count($keywords) > 0) {
                    $relevance += 0.4 * ($matchCount / count($keywords));
                }
            }
        }
        
        // 检查内容匹配度
        $contentLower = mb_strtolower($entry->answer);
        if (mb_strpos($contentLower, $query) !== false) {
            $relevance += 0.3; // 内容完全包含查询
        } else {
            // 检查查询中的关键词是否在内容中
            $keywords = $this->extractKeywords($query);
            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (mb_strpos($contentLower, mb_strtolower($keyword)) !== false) {
                    $matchCount++;
                }
            }
            if (count($keywords) > 0) {
                $relevance += 0.2 * ($matchCount / count($keywords));
            }
        }
        
        // 检查知识库条目的关键词匹配度
        if (!empty($entry->keywords)) {
            $entryKeywords = explode(',', mb_strtolower($entry->keywords));
            $entryKeywords = array_map('trim', $entryKeywords);
            
            // 检查查询是否包含任何条目关键词
            foreach ($entryKeywords as $keyword) {
                if (!empty($keyword) && mb_strpos($query, $keyword) !== false) {
                    $relevance += 0.3; // 查询包含条目关键词，高相关度
                    break;
                }
            }
            
            // 检查查询中的关键词是否匹配条目关键词
            $queryKeywords = $this->extractKeywords($query);
            $matchCount = 0;
            foreach ($queryKeywords as $queryKeyword) {
                foreach ($entryKeywords as $entryKeyword) {
                    if (!empty($entryKeyword) && (
                        $entryKeyword === mb_strtolower($queryKeyword) || 
                        mb_strpos($entryKeyword, mb_strtolower($queryKeyword)) !== false ||
                        mb_strpos(mb_strtolower($queryKeyword), $entryKeyword) !== false
                    )) {
                        $matchCount++;
                        break;
                    }
                }
            }
            if (count($queryKeywords) > 0) {
                $relevance += 0.2 * ($matchCount / count($queryKeywords));
            }
        }
        
        // 根据条目类型调整相关度
        if ($entry->type === 'qa' && !empty($entry->question)) {
            $relevance *= 1.2; // 提高QA类型的权重
        } elseif ($entry->type === 'content') {
            // 对于Content类型，如果关键词匹配度高，提高权重
            if (!empty($entry->keywords)) {
                $entryKeywords = explode(',', mb_strtolower($entry->keywords));
                $entryKeywords = array_map('trim', $entryKeywords);
                $queryLower = mb_strtolower($query);
                
                foreach ($entryKeywords as $keyword) {
                    if (!empty($keyword) && mb_strpos($queryLower, $keyword) !== false) {
                        $relevance *= 1.3; // 如果查询包含关键词，显著提高权重
                        break;
                    }
                }
            }
        }
        
        // 限制相关度在0-1范围内
        return min(1.0, max(0.0, $relevance));
    }

    /**
     * 从查询中提取关键词
     *
     * @param string $query 查询字符串
     * @return array 关键词数组
     */
    protected function extractKeywords(string $query): array
    {
        // 移除特殊字符
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        
        // 分词
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        
        // 过滤停用词
        $stopwords = ['的', '了', '是', '在', '我', '有', '和', '就', '不', '人', '都', '一', '一个', '上', '也', '很', '到', '说', '要', '去', '你', '会', '着', '没有', '看', '好', '自己', '这'];
        $words = array_filter($words, function ($word) use ($stopwords) {
            return !in_array($word, $stopwords) && mb_strlen($word) > 1;
        });
        
        return array_values($words);
    }
} 