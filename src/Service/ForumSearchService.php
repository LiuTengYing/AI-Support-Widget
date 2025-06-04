<?php

namespace LeoT\FlarumAiSupport\Service;

use Flarum\Discussion\DiscussionRepository;
use Flarum\Post\PostRepository;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use LeoT\FlarumAiSupport\Api\OpenAiService;
use LeoT\FlarumAiSupport\Api\DeepSeekService;
// 移除 Illuminate\Support\Facades\Cache 引用
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ForumSearchService
{
    protected $discussions;
    protected $posts;
    protected $settings;
    protected $cache; // 添加缓存属性
    
    public function __construct(
        DiscussionRepository $discussions,
        PostRepository $posts,
        SettingsRepositoryInterface $settings,
        CacheRepository $cache // 通过依赖注入获取缓存实例
    ) {
        $this->discussions = $discussions;
        $this->posts = $posts;
        $this->settings = $settings;
        $this->cache = $cache; // 保存缓存实例
    }
    
    protected function sanitizeKeyword($keyword)
    {
        // Check if keyword is null or empty
        if ($keyword === null || $keyword === '') {
            return '';
        }
        
        // Convert to string if not already
        $keyword = (string) $keyword;
        
        // Remove special characters that could cause issues in LIKE queries
        // Use str_replace instead of regex for better reliability
        $keyword = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $keyword);
        
        return trim($keyword);
    }
    
    public function searchRelevantPosts(string $query, User $actor): array
    {
        $searchLimit = (int) $this->settings->get('leot-ai-support-widget.search_limit', 5);
        
        // Generate cache key
        $cacheKey = 'ai_support_search_' . md5($query . '_' . $actor->id);
        
        $cachedResults = $this->cache->get($cacheKey);
        if ($cachedResults !== null) {
            // 添加检查：验证缓存的结果是否仍然有效（检查URL对应的帖子是否存在）
            $validResults = [];
            foreach ($cachedResults as $result) {
                // 从URL中提取讨论ID
                if (preg_match('/\/d\/(\d+)(?:\/\d+)?$/', $result['url'], $matches)) {
                    $discussionId = (int) $matches[1];
                    
                    // 检查讨论是否仍然存在且对当前用户可见
                    $discussion = $this->discussions->query()
                        ->where('id', $discussionId)
                        ->whereVisibleTo($actor)
                        ->first();
                    
                    if ($discussion) {
                        $validResults[] = $result;
                    }
                } else {
                    // 无法解析URL，保留结果
                    $validResults[] = $result;
                }
            }
            
            // 如果有无效结果，更新缓存
            if (count($validResults) !== count($cachedResults)) {
                $this->cache->put($cacheKey, $validResults, 300);
                return $validResults;
            }
            
            return $cachedResults;
        }
        
        $keywords = $this->extractKeywords($query);
        
        // Filter out empty keywords and sanitize
        $keywords = array_filter(array_map([$this, 'sanitizeKeyword'], $keywords), function($keyword) {
            return !empty($keyword);
        });
        
        if (empty($keywords)) {
            return [];
        }
        
        try {
            // 搜索相关讨论
        $discussions = $this->discussions
            ->query()
            ->where(function (Builder $q) use ($keywords) {
                foreach ($keywords as $keyword) {
                        if (mb_strlen($keyword) > 1) {
                    $q->orWhere('title', 'LIKE', "%{$keyword}%");
                            // 同时在帖子内容中搜索
                            $q->orWhereHas('posts', function (Builder $query) use ($keyword) {
                                $query->where('content', 'LIKE', "%{$keyword}%");
                            });
                        }
                }
            })
            ->whereVisibleTo($actor)
            ->orderBy('comment_count', 'desc')
            ->orderBy('created_at', 'desc')
                ->limit($searchLimit * 3) // 获取更多结果然后排序筛选
            ->get();
            
        $results = [];
        
        foreach ($discussions as $discussion) {
                // 首先添加第一个帖子
            $firstPost = $discussion->firstPost;
            if ($firstPost && $firstPost->isVisibleTo($actor)) {
                $results[] = [
                    'title' => $discussion->title,
                    'content' => $this->cleanContent($firstPost->content),
                    'url' => $this->generateDiscussionUrl($discussion->id),
                    'created_at' => $discussion->created_at->toDateTimeString(),
                    'comment_count' => $discussion->comment_count,
                    'relevance_score' => $this->calculateRelevance($discussion->title . ' ' . $firstPost->content, $keywords)
                ];
            }
                
                // 然后添加讨论中的其他回复（非首贴）
                $posts = $this->posts
                    ->query()
                    ->where('discussion_id', $discussion->id)
                    ->where('number', '>', 1) // 跳过第一个帖子（已添加）
                    ->where(function (Builder $q) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            if (mb_strlen($keyword) > 1) {
                                $q->orWhere('content', 'LIKE', "%{$keyword}%");
                            }
                        }
                    })
                    ->whereVisibleTo($actor)
                    ->limit(3) // 每个讨论最多添加3个额外回复
                    ->get();
                
                foreach ($posts as $post) {
                    $postContent = $this->cleanContent($post->content);
                    // 跳过过长或过短的帖子
                    if (mb_strlen($postContent) < 5 || mb_strlen($postContent) > 2000) {
                        continue;
                    }
                    
                    $relevance = $this->calculateRelevance($postContent, $keywords);
                    
                    // 只添加相关性足够高的帖子
                    if ($relevance > 1) {
                        $results[] = [
                            'title' => $discussion->title . ' (Reply #' . $post->number . ')',
                            'content' => $postContent,
                            'url' => $this->generatePostUrl($discussion->id, $post->number),
                            'created_at' => $post->created_at->toDateTimeString(),
                            'comment_count' => $discussion->comment_count,
                            'relevance_score' => $relevance
                        ];
                    }
                }
            }
            
            // 按相关性排序
        usort($results, function ($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
            $finalResults = array_slice($results, 0, $searchLimit);
            
            // 将结果存入缓存，有效期5分钟
            $this->cache->put($cacheKey, $finalResults, 300);
            
            return $finalResults;
        } catch (\Exception $e) {
            error_log('[AI Support] Search error: ' . $e->getMessage());
            error_log('[AI Support] Search error trace: ' . $e->getTraceAsString());
            return [];
        }
    }
    
    private function extractKeywords(string $text): array
    {
        // 检测是否包含中文
        $containsChinese = preg_match('/[\p{Han}]/u', $text);
        
        if ($containsChinese) {
            // 中文关键词提取
            return $this->extractChineseKeywords($text);
        } else {
            // 英文关键词提取
            return $this->extractEnglishKeywords($text);
        }
    }
    
    private function extractChineseKeywords(string $text): array
    {
        // 移除特殊字符，但保留中文字符
        $text = preg_replace('/[^\p{Han}\p{L}\p{N}\s]/u', ' ', $text);
        
        // 对于中文，我们直接按词语长度分割，而不是按空格
        // 中文搜索策略：提取2-4个字的组合作为关键词
        $keywords = [];
        $textLength = mb_strlen($text);
        
        // 提取原始查询中最长的词组（可能是产品名称、型号等）
        $originalWords = explode(' ', $text);
        foreach ($originalWords as $word) {
            if (mb_strlen($word) > 1) {
                $keywords[] = $word;
            }
        }
        
        // 分割成单个中文字符
        $chars = [];
        for ($i = 0; $i < $textLength; $i++) {
            $char = mb_substr($text, $i, 1);
            if (preg_match('/[\p{Han}]/u', $char)) {
                $chars[] = $char;
            }
        }
        
        // 生成2-3个字符的组合作为关键词
        $charsCount = count($chars);
        for ($i = 0; $i < $charsCount - 1; $i++) {
            // 2个字符组合
            if ($i < $charsCount - 1) {
                $keyword = $chars[$i] . $chars[$i + 1];
                if (!in_array($keyword, $keywords)) {
                    $keywords[] = $keyword;
                }
            }
            
            // 3个字符组合
            if ($i < $charsCount - 2) {
                $keyword = $chars[$i] . $chars[$i + 1] . $chars[$i + 2];
                if (!in_array($keyword, $keywords)) {
                    $keywords[] = $keyword;
                }
            }
        }
        
        return $keywords;
    }
    
    private function extractEnglishKeywords(string $text): array
    {
        // 移除特殊字符
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        
        // 分割成单词
        $words = explode(' ', strtolower($text));
        
        // 过滤停用词和短词
        $stopwords = ['a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'like', 'through', 'over', 'before', 'after', 'between', 'after', 'since', 'of', 'from'];
        $keywords = array_filter($words, function($word) use ($stopwords) {
            return !in_array($word, $stopwords) && strlen($word) > 2;
        });
        
        return array_values($keywords);
    }
    
    private function cleanContent(string $content): string
    {
        // 移除HTML标签
        $content = strip_tags($content);
        // 移除多余的空白字符
        $content = preg_replace('/\s+/', ' ', $content);
        // 移除特殊字符
        $content = preg_replace('/[^\p{L}\p{N}\p{P}\s]/u', '', $content);
        return trim($content);
    }
    
    private function generateDiscussionUrl(int $discussionId): string
    {
        $baseUrl = $this->getForumBaseUrl();
        return $baseUrl . '/d/' . $discussionId;
    }
    
    private function generatePostUrl(int $discussionId, int $postNumber): string
    {
        $baseUrl = $this->getForumBaseUrl();
        return $baseUrl . '/d/' . $discussionId . '/' . $postNumber;
    }
    
    /**
     * 获取论坛基础URL
     * 
     * @return string
     */
    private function getForumBaseUrl(): string
    {
        $baseUrl = $this->settings->get('forum_url');
        
        if (empty($baseUrl)) {
            // 尝试从其他设置中获取URL
            $baseUrl = $this->settings->get('base_url');
        }
        
        if (empty($baseUrl)) {
            // 使用默认值
            $baseUrl = 'http://localhost';
        }
        
        // 确保没有尾部斜杠
        return rtrim($baseUrl, '/');
    }
    
    /**
     * 计算内容与关键词的相关性
     * 
     * @param string $content
     * @param array $keywords
     * @return float
     */
    private function calculateRelevance(string $content, array $keywords): float
    {
        $content = strtolower($content);
        $relevance = 0;
        
        foreach ($keywords as $keyword) {
            $keyword = strtolower($keyword);
            $count = substr_count($content, $keyword);
            
            if ($count > 0) {
                // 关键词出现越多，相关性越高
                $relevance += $count;
                
                // 标题中的关键词权重更高
                if (mb_stripos($content, $keyword) !== false && mb_stripos($content, $keyword) < 100) {
                    $relevance += 2;
                }
            }
        }
        
        // 考虑内容长度的影响
        $contentLength = mb_strlen($content);
        if ($contentLength > 0) {
            // 内容不要太短也不要太长
            if ($contentLength < 50) {
                $relevance *= 0.5; // 太短的内容可能不够有用
            } else if ($contentLength > 5000) {
                $relevance *= 0.7; // 太长的内容可能包含太多无关信息
            }
        }
        
        return $relevance;
    }
}