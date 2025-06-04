<?php

namespace LeoT\FlarumAiSupport\Api;

use Flarum\User\User;
use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAiService implements AiServiceInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    
    /**
     * @var Client
     */
    protected $client;
    
    /**
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->client = new Client(['timeout' => (int)$settings->get('leot-ai-support-widget.timeout', 60)]);
    }
    
    /**
     * 获取AI响应
     *
     * @param string $message 用户消息
     * @param User $user 当前用户
     * @param array $conversationHistory 对话历史
     * @return string AI响应文本
     */
    public function getResponse(string $message, User $user, array $conversationHistory = []): string
    {
        $apiKey = $this->settings->get('leot-ai-support-widget.api_key');
        $model = $this->settings->get('leot-ai-support-widget.model_name', 'gpt-3.5-turbo');
        $maxTokens = (int)$this->settings->get('leot-ai-support-widget.max_tokens', 1000);
        
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key is not configured.');
        }
        
        try {
            // 构建消息数组
            $messages = [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant for the forum. Be concise and helpful. IMPORTANT: You MUST STRICTLY respond in the EXACT SAME LANGUAGE that the user is using. If the user asks in English, you MUST respond in English ONLY. If the user asks in Chinese, you MUST respond in Chinese ONLY. NEVER mix languages in your response.'
                ]
            ];
            
            // 添加对话历史
            if (!empty($conversationHistory)) {
                foreach ($conversationHistory as $historyMessage) {
                    $messages[] = [
                        'role' => $historyMessage['role'],
                        'content' => $historyMessage['content']
                    ];
                }
            }
            
            // 添加当前消息
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.7,
                    'user' => 'user_' . $user->id
                ],
                'timeout' => 60,
                'connect_timeout' => 10
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return $result['choices'][0]['message']['content'];
            }
            
            throw new \Exception('Invalid response from OpenAI API.');
        } catch (RequestException $e) {
            error_log('OpenAI API Error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'timed out') !== false || strpos($e->getMessage(), 'timeout') !== false) {
                // 检测用户消息中是否包含中文字符
                if (preg_match('/[\p{Han}]/u', $message)) {
                    return "对不起，AI服务暂时无法连接，请稍后再试。可能是网络连接问题或者服务器繁忙。\n\n如果您有具体问题，请尝试在论坛中发帖或联系技术团队。";
                } else {
                    return "Sorry, the AI service is temporarily unavailable. Please try again later. This might be due to network connectivity issues or server load.\n\nIf you have a specific question, please consider posting in the forum or contacting the technical team.";
                }
            }
            
            throw new \Exception('OpenAI API Error: ' . $e->getMessage());
        }
    }
    
    /**
     * 测试API连接
     *
     * @return bool 连接是否成功
     */
    public function testConnection(): bool
    {
        $apiKey = $this->settings->get('leot-ai-support-widget.api_key');
        
        if (empty($apiKey)) {
            return false;
        }
        
        try {
            // 简单的模型列表请求，这是一个轻量级的API调用
            $response = $this->client->get('https://api.openai.com/v1/models', [
            'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ]
        ]);
            
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public function generateResponse(string $message, array $conversationHistory = [], array $searchResults = []): array
    {
        $systemPrompt = $this->buildSystemPrompt($searchResults);
        $messages = $this->buildMessages($systemPrompt, $message, $conversationHistory);
        
        try {
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->settings->get('leot-ai-support-widget.api_key'),
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->settings->get('leot-ai-support-widget.model_name', 'gpt-3.5-turbo'),
                    'messages' => $messages,
                    'max_tokens' => (int) $this->settings->get('leot-ai-support-widget.max_tokens', 1000),
                    'temperature' => 0.7,
                    'stream' => false
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            return [
                'content' => $data['choices'][0]['message']['content'] ?? 'Sorry, I could not generate a response.',
                'references' => $this->extractReferences($searchResults),
                'tokens_used' => $data['usage']['total_tokens'] ?? 0
            ];
            
        } catch (RequestException $e) {
            error_log('[AI Support] OpenAI API request failed: ' . $e->getMessage());
            throw new \Exception('OpenAI API request failed: ' . $e->getMessage());
        }
    }
    
    public function getSupportedModels(): array
    {
        return [
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo-preview' => 'GPT-4 Turbo',
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K'
        ];
    }
    
    private function buildSystemPrompt(array $searchResults): string
    {
        $prompt = "You are a helpful AI assistant for a Flarum forum community. ";
        $prompt .= "Your role is to help users by answering their questions based on the forum's existing content and your general knowledge. ";
        $prompt .= "Always be friendly, concise, and helpful. ";
        $prompt .= "IMPORTANT: You MUST STRICTLY respond in the EXACT SAME LANGUAGE that the user is using. If the user asks in English, you MUST respond in English ONLY. If the user asks in Chinese, you MUST respond in Chinese ONLY. NEVER mix languages in your response. ";
        
        if (!empty($searchResults)) {
            $prompt .= "\n\nIMPORTANT INSTRUCTION: When answering, you MUST directly use and cite the EXACT solutions from the forum posts. DO NOT create your own solutions when forum solutions exist.\n\n";
            $prompt .= "Here are relevant discussions from the forum that directly answer the user's question:\n\n";
            
            foreach ($searchResults as $index => $result) {
                $prompt .= "Reference " . ($index + 1) . ":\n";
                $prompt .= "Title: " . $result['title'] . "\n";
                $prompt .= "Content: " . substr($result['content'], 0, 800) . "...\n";
                $source = isset($result['source']) ? $result['source'] : 'forum';
                $prompt .= "Source: " . $source . "\n";
                if (isset($result['url'])) {
                    $prompt .= "URL: " . $result['url'] . "\n";
                }
                $prompt .= "\n";
            }
            
            $prompt .= "CRITICAL INSTRUCTIONS:\n";
            $prompt .= "1. When referencing forum content, you MUST use the reference number (e.g., 'According to Reference 1...')\n";
            $prompt .= "2. You MUST directly quote the EXACT content from these references as your primary answer - word for word when possible\n";
            $prompt .= "3. If a reference contains a specific solution like 'Check that the canbus box is connected correctly', your response MUST include this EXACT phrase\n";
            $prompt .= "4. Do not generalize or rewrite forum solutions - quote them directly\n";
            $prompt .= "5. NEVER provide translations of content. ONLY respond in the language the user is using\n";
            $prompt .= "6. Your response is incorrect if it creates general advice when specific forum advice exists\n";
            $prompt .= "7. Prioritize direct quotations from the forum over your own knowledge\n";
            $prompt .= "8. CRITICAL: If the user asks in English, your ENTIRE response MUST be in English ONLY\n";
            $prompt .= "9. CRITICAL: If the user asks in Chinese, your ENTIRE response MUST be in Chinese ONLY\n";
            $prompt .= "10. IMPORTANT: If a reference contains URLs or links, you MUST include these exact links in your response\n";
            $prompt .= "11. When suggesting next steps for unresolved issues, recommend posting in the forum or contacting the technical team, NOT contacting 'professional technicians' or 'manufacturer support'\n";
            $prompt .= "12. CRITICAL: You MUST include ALL content from knowledge base entries in your response. Do not summarize or omit information from knowledge base entries. First present the complete knowledge base content, then add your own supplementary information if needed\n";
            $prompt .= "13. DO NOT include phrases like 'based on Reference X' or 'According to Reference X' in your response. Instead, present the information directly without mentioning that it comes from a reference\n";
            $prompt .= "14. ALWAYS include ALL links and URLs from the references, including image links (like Imgur) and video links (like YouTube)\n";
        }
        
        return $prompt;
    }
    
    private function buildMessages(string $systemPrompt, string $currentMessage, array $history): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Add conversation history
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? ''
            ];
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $currentMessage
        ];
        
        return $messages;
    }
    
    private function extractReferences(array $searchResults): array
    {
        $references = [];
        foreach ($searchResults as $index => $result) {
            $references[] = [
                'title' => $result['title'],
                'url' => $result['url'],
                'reference_number' => $index + 1
            ];
        }
        return $references;
    }
    
    /**
     * 使用上下文获取AI响应
     *
     * @param string $message 用户消息
     * @param User $user 当前用户
     * @param string $systemPrompt 系统提示
     * @param array $searchResults 论坛搜索结果
     * @param array $conversationHistory 对话历史
     * @return string AI响应文本
     */
    public function getResponseWithContext(string $message, User $user, string $systemPrompt, array $searchResults, array $conversationHistory = []): string
    {
        $apiKey = $this->settings->get('leot-ai-support-widget.api_key');
        $model = $this->settings->get('leot-ai-support-widget.model_name', 'gpt-3.5-turbo');
        $maxTokens = (int)$this->settings->get('leot-ai-support-widget.max_tokens', 1000);
        
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key is not configured.');
        }
        
        try {
            // 构建消息数组
            $messages = [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ]
            ];
            
            // 添加对话历史
            if (!empty($conversationHistory)) {
                foreach ($conversationHistory as $historyMessage) {
                    $messages[] = [
                        'role' => $historyMessage['role'],
                        'content' => $historyMessage['content']
                    ];
                }
            }
            
            // 添加当前消息
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $model,
                    'messages' => $messages,
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.7,
                    'user' => 'user_' . $user->id
                ],
                'timeout' => 60,
                'connect_timeout' => 10
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (isset($result['choices'][0]['message']['content'])) {
                return $result['choices'][0]['message']['content'];
            }
            
            throw new \Exception('Invalid response from OpenAI API.');
        } catch (RequestException $e) {
            error_log('OpenAI API Error: ' . $e->getMessage());
            
            if (strpos($e->getMessage(), 'timed out') !== false || strpos($e->getMessage(), 'timeout') !== false) {
                // 检测用户消息中是否包含中文字符
                if (preg_match('/[\p{Han}]/u', $message)) {
                    return "对不起，AI服务暂时无法连接，请稍后再试。可能是网络连接问题或者服务器繁忙。\n\n如果您有具体问题，请尝试在论坛中发帖或联系技术团队。";
                } else {
                    return "Sorry, the AI service is temporarily unavailable. Please try again later. This might be due to network connectivity issues or server load.\n\nIf you have a specific question, please consider posting in the forum or contacting the technical team.";
                }
            }
            
            throw new \Exception('OpenAI API Error: ' . $e->getMessage());
        }
    }
}