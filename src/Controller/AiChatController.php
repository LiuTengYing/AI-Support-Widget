<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use LeoT\FlarumAiSupport\Services\AiProviderService;
use LeoT\FlarumAiSupport\Service\ForumSearchService;
use LeoT\FlarumAiSupport\Service\KnowledgeBaseSearchService;
use LeoT\FlarumAiSupport\Api\Serializer\AiResponseSerializer;
use LeoT\FlarumAiSupport\Services\UsageCounterService;
use Psr\Http\Message\ServerRequestInterface;
use Flarum\Api\Controller\AbstractShowController;
use Tobscure\JsonApi\Document;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class AiChatController extends AbstractShowController
{
    /**
     * 序列化器类
     *
     * @var string
     */
    public $serializer = AiResponseSerializer::class;
    
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    
    /**
     * @var AiProviderService
     */
    protected $aiProvider;
    
    /**
     * @var ForumSearchService
     */
    protected $forumSearch;
    
    /**
     * @var KnowledgeBaseSearchService
     */
    protected $kbSearch;
    
    /**
     * @var UsageCounterService
     */
    protected $usageCounter;
    
    /**
     * @param SettingsRepositoryInterface $settings
     * @param AiProviderService $aiProvider
     * @param ForumSearchService $forumSearch
     * @param KnowledgeBaseSearchService $kbSearch
     * @param UsageCounterService $usageCounter
     */
    public function __construct(
        SettingsRepositoryInterface $settings, 
        AiProviderService $aiProvider,
        ForumSearchService $forumSearch,
        KnowledgeBaseSearchService $kbSearch,
        UsageCounterService $usageCounter
    ) {
        $this->settings = $settings;
        $this->aiProvider = $aiProvider;
        $this->forumSearch = $forumSearch;
        $this->kbSearch = $kbSearch;
        $this->usageCounter = $usageCounter;
    }
    
    /**
     * 获取数据
     *
     * @param ServerRequestInterface $request
     * @param Document $document
     * @return array
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $data = Arr::get($request->getParsedBody(), 'message');
        $history = Arr::get($request->getParsedBody(), 'history', []);
        
        // 验证用户输入
        if ($data === null || !is_string($data)) {
            // 创建一个带有id的对象
            $response = new \stdClass();
            $response->id = '0';
            $response->response = 'Please provide a valid message.';
            $response->references = [];
            return $response;
        }
        
        // 处理空消息或只有空格的消息
        $trimmedData = trim($data);
        if ($trimmedData === '') {
            // 创建一个带有id的对象
            $response = new \stdClass();
            $response->id = '0';
            $response->response = 'Please provide a non-empty message.';
            $response->references = [];
            return $response;
        }
        
        // 处理简短消息，如"hello"、"hi"等
        $simpleGreetings = ['hello', 'hi', 'hey', 'hello?', 'hi?', 'hey?', '你好', '嗨', '您好'];
        if (in_array(strtolower($trimmedData), $simpleGreetings)) {
            // 获取每日请求限制设置
            $settings = app('flarum.settings');
            $dailyLimit = (int) $settings->get('leot-ai-support-widget.daily_requests_limit', 1);
            if ($dailyLimit < 1) $dailyLimit = 1;
            
            try {
                // 增加使用次数（对所有用户，包括管理员）
                $this->usageCounter->increment($actor->id);
            } catch (\Exception $e) {
                error_log('[AI Support] Error incrementing count for simple greeting: ' . $e->getMessage());
            }
            
            // 创建一个简单的问候回复
            $response = new \stdClass();
            $response->id = '1';
            $response->response = 'Hello! I\'m the Forum AI Assistant. How can I help you today? Feel free to ask me any questions about car navigation systems, Android head units, or other related topics.';
            $response->references = [];
            return $response;
        }
            
        // 获取每日请求限制设置
        $settings = app('flarum.settings');
        $dailyLimit = (int) $settings->get('leot-ai-support-widget.daily_requests_limit', 1);
        if ($dailyLimit < 1) $dailyLimit = 1;
        
        try {
            // 获取今日使用次数
            $todayCount = $this->usageCounter->getTodayCount($actor->id);
                    
            // 检查是否超过限制（只对非管理员用户检查限制）
            if (!$actor->isAdmin() && $todayCount >= $dailyLimit) {
                $response = new \stdClass();
                $response->id = '429';
                $response->response = 'You have reached your daily usage limit (' . $dailyLimit . ' requests). Please try again tomorrow.';
                $response->references = [];
                return $response;
            }
                    
            // 增加使用次数（对所有用户，包括管理员）
            $this->usageCounter->increment($actor->id);
        } catch (\Exception $e) {
            error_log('[AI Support] Error checking usage limit: ' . $e->getMessage());
        }
        
        try {
            // 搜索相关论坛内容
            $forumSearchResults = [];
            try {
                $forumSearchResults = $this->forumSearch->searchRelevantPosts($data, $actor);
            } catch (\Exception $e) {
                error_log('[AI Support] Forum search error: ' . $e->getMessage());
                // 搜索失败时使用空数组继续
                $forumSearchResults = [];
            }
            
            // 搜索知识库内容
            $kbSearchResults = [];
            try {
                $kbSearchResults = $this->kbSearch->search($data, 5);
            } catch (\Exception $e) {
                error_log('[AI Support] Knowledge base search error: ' . $e->getMessage());
                // 搜索失败时使用空数组继续
                $kbSearchResults = [];
            }
            
            // 合并搜索结果
            $searchResults = array_merge($forumSearchResults, $kbSearchResults);
            
            // 如果搜索结果为空，检查是否是简单问题，可以直接回答
            if (empty($searchResults)) {
                // 简单问题列表及其回答
                $simpleQuestions = [
                    'hello' => 'Hello! How can I assist you today with your car navigation or Android head unit questions?',
                    'hi' => 'Hi there! How can I help you with your car navigation system or Android head unit today?',
                    'hey' => 'Hey! What questions do you have about car navigation or Android head units?',
                    '你好' => '你好！我能帮您解答关于车载导航或安卓主机的问题吗？',
                    '嗨' => '嗨！有什么关于车载导航或安卓主机的问题需要帮助吗？',
                    '您好' => '您好！请问有什么关于车载导航系统或安卓主机的问题需要我帮忙解答吗？'
                ];
                
                $lowerMessage = strtolower(trim($data));
                if (array_key_exists($lowerMessage, $simpleQuestions)) {
                    $response = new \stdClass();
                    $response->id = '1';
                    $response->response = $simpleQuestions[$lowerMessage];
                    $response->references = [];
                    return $response;
                }
            }
            
            try {
                // 计算关键词匹配度来评估相关性
                $searchResults = $this->calculateRelevanceScores($data, $searchResults);
            } catch (\Exception $e) {
                error_log('[AI Support] Error calculating relevance scores: ' . $e->getMessage());
                // 如果计算相关性失败，继续使用未排序的结果
            }
            
            // 获取AI响应
            $aiResponse = $this->aiProvider->getCompletion($data, $actor, $searchResults, $history);
            
            // 创建响应对象
            $response = new \stdClass();
            $response->id = '2';
            $response->response = $aiResponse;
            $response->references = $this->extractReferences($searchResults);
            return $response;
        } catch (\Exception $e) {
            error_log('[AI Support] Error: ' . $e->getMessage());
            error_log('[AI Support] Error trace: ' . $e->getTraceAsString());
            
            $response = new \stdClass();
            $response->id = '500';
            $response->response = 'Sorry, an error occurred while processing your request. Please try again later.';
            $response->references = [];
            return $response;
        }
    }
    
    /**
     * 从搜索结果中提取引用信息
     *
     * @param array $searchResults
     * @return array
     */
    private function extractReferences(array $searchResults): array
    {
        $references = [];
        
        foreach ($searchResults as $result) {
            // 只添加有URL的结果作为引用
            if (isset($result['url'])) {
                $references[] = [
                    'title' => $result['title'] ?? ($result['question'] ?? 'Content'),
                    'url' => $result['url'],
                    'source' => $result['source'] ?? 'forum'
                ];
            }
        }
        
        return $references;
    }
    
    /**
     * 计算搜索结果的相关性分数
     *
     * @param string $query
     * @param array $results
     * @return array
     */
    private function calculateRelevanceScores(string $query, array $results): array
    {
        // 如果结果为空，直接返回
        if (empty($results)) {
            return [];
        }
        
        // 提取查询中的关键词
        $keywords = $this->extractKeywords($query);
        
        // 如果没有有效的关键词，返回原始结果
        if (empty($keywords)) {
            return $results;
        }
        
        // 为每个结果计算相关性分数
        foreach ($results as &$result) {
            $content = '';
            
            // 根据结果类型获取内容
            if (isset($result['content'])) {
                $content = $result['content'];
            } elseif (isset($result['question']) && isset($result['answer'])) {
                // 知识库条目可能有问题和答案字段
                $content = $result['question'] . ' ' . $result['answer'];
            }
            
            if (!empty($content)) {
                // 计算关键词匹配分数
                $result['relevance'] = $this->calculateKeywordMatchScore($content, $keywords);
                
                // 如果相关性分数高，记录日志
                if ($result['relevance'] > 0.7) {
                    // 记录高相关性内容的前100个字符
                    $score = number_format($result['relevance'], 2);
                }
            } else {
                // 如果没有内容，设置一个低相关性分数
                $result['relevance'] = 0.1;
            }
        }
        
        // 按相关性分数降序排序
        usort($results, function($a, $b) {
            $scoreA = $a['relevance'] ?? 0;
            $scoreB = $b['relevance'] ?? 0;
            return $scoreB <=> $scoreA;
        });
        
        return $results;
    }
    
    /**
     * 计算文本与关键词的匹配分数
     *
     * @param string $text
     * @param array $keywords
     * @return float
     */
    private function calculateKeywordMatchScore(string $text, array $keywords): float
    {
        $text = strtolower($text);
        $totalKeywords = count($keywords);
        $matchedKeywords = 0;
        
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                $matchedKeywords++;
            }
        }
        
        // 计算匹配比例
        return $totalKeywords > 0 ? $matchedKeywords / $totalKeywords : 0;
    }
    
    /**
     * 从查询中提取关键词
     *
     * @param string $query
     * @return array
     */
    private function extractKeywords(string $query): array
    {
        // 简单的关键词提取，移除停用词并拆分
        $stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 
                      'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'like', 'of',
                      'from', 'how', 'what', 'when', 'where', 'who', 'why', 'which', 'that',
                      '的', '了', '和', '是', '在', '我', '有', '你', '他', '她', '它',
                      '们', '这', '那', '个', '就', '也', '要', '会', '对', '能'];
        
        // 转换为小写并移除标点
        $query = strtolower($query);
        $query = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $query);
        
        // 分割为单词
        $words = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        
        // 过滤掉停用词和短词
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && mb_strlen($word) > 1;
        });
        
        return array_values($keywords);
    }
} 