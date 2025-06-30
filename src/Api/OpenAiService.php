<?php

namespace LeoT\FlarumAiSupport\Api;

use Flarum\User\User;
use Flarum\Settings\SettingsRepositoryInterface;
use OpenAI;
use OpenAI\Client;

class OpenAiService implements AiServiceInterface
{
    protected SettingsRepositoryInterface $settings;
    protected ?Client $client = null;
    
    // Safe default configuration - no sensitive data
    private const DEFAULT_BASE_URI = 'https://api.openai.com/v1/';
    private const DEFAULT_MODEL = 'gpt-3.5-turbo';
    private const DEFAULT_MAX_TOKENS = 1000;
    private const DEFAULT_TIMEOUT = 120;
    private const DEFAULT_CONNECT_TIMEOUT = 60;
    
    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }
    
    /**
     * Get configuration value with fallback to default
     */
    private function getConfigValue(string $key, string $default = ''): string
    {
        $value = $this->settings->get($key);
        return !empty($value) ? $value : $default;
    }
    
    /**
     * Get OpenAI client instance
     */
    protected function getClient(): Client
    {
        if ($this->client === null) {
            // Get API key from settings - REQUIRED
            $apiKey = $this->settings->get('leot-ai-support-widget.api_key');
            
            if (empty($apiKey)) {
                throw new \Exception('OpenAI API key is not configured. Please set your API key in the Flarum admin panel under AI Support Widget settings.');
            }
            
            // Configure HTTP client with enhanced proxy support and timeout settings
            $httpClientConfig = [
                'timeout' => self::DEFAULT_TIMEOUT,
                'connect_timeout' => self::DEFAULT_CONNECT_TIMEOUT,
                'verify' => false,
                'http_errors' => false,
            ];
            
            // Add proxy configuration if available
            $proxyUrl = $this->settings->get('leot-ai-support-widget.proxy_url');
            if (!empty($proxyUrl)) {
                $httpClientConfig['proxy'] = [
                    'http' => $proxyUrl,
                    'https' => $proxyUrl,
                ];
            }
            
            // Add custom base URI support for proxy services
            $baseUri = $this->getConfigValue('leot-ai-support-widget.base_uri', self::DEFAULT_BASE_URI);
            
            // Create HTTP client with custom configuration
            $httpClient = new \GuzzleHttp\Client($httpClientConfig);
            
            // Use official OpenAI client with custom HTTP client
            $clientBuilder = OpenAI::factory()
                ->withApiKey($apiKey)
                ->withHttpClient($httpClient);
                
            // Set custom base URI if provided
            if (!empty($baseUri)) {
                $clientBuilder = $clientBuilder->withBaseUri($baseUri);
            }
            
            $this->client = $clientBuilder->make();
        }
        
        return $this->client;
    }
    
    /**
     * Get AI response
     */
    public function getResponse(string $message, User $user, array $conversationHistory = []): string
    {
        try {
            $model = $this->getConfigValue('leot-ai-support-widget.model_name', self::DEFAULT_MODEL);
            $maxTokens = (int)$this->getConfigValue('leot-ai-support-widget.max_tokens', (string)self::DEFAULT_MAX_TOKENS);
            
            // Build system prompt
            $systemPrompt = $this->buildSystemPrompt($user);
            
            // Build messages array
            $messages = $this->buildMessages($systemPrompt, $message, $conversationHistory);
            
            $response = $this->getClient()->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);
            
            return $response->choices[0]->message->content ?? 'Sorry, I could not generate a response.';
            
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'cURL error 28') !== false || 
                strpos($e->getMessage(), 'timeout') !== false) {
                return 'Connection timeout. Please check your network connection or try again later.';
            }
            
            return 'An error occurred while processing your request: ' . $e->getMessage();
        }
    }
    
    /**
     * Get AI response with context (matches interface signature)
     */
    public function getResponseWithContext(string $message, User $user, string $systemPrompt, array $searchResults, array $conversationHistory = []): string
    {
        try {
            $model = $this->getConfigValue('leot-ai-support-widget.model_name', self::DEFAULT_MODEL);
            $maxTokens = (int)$this->getConfigValue('leot-ai-support-widget.max_tokens', (string)self::DEFAULT_MAX_TOKENS);
            
            // Use the enhanced system prompt with search results
            $enhancedSystemPrompt = $this->buildSystemPrompt($user, $searchResults);
            
            // Build messages array
            $messages = $this->buildMessages($enhancedSystemPrompt, $message, $conversationHistory);
            
            $response = $this->getClient()->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);
            
            return $response->choices[0]->message->content ?? 'Sorry, I could not generate a response.';
            
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'cURL error 28') !== false || 
                strpos($e->getMessage(), 'timeout') !== false) {
                return 'Connection timeout. Please check your network connection or try again later.';
            }
            
            return 'An error occurred while processing your request: ' . $e->getMessage();
        }
    }
    
    /**
     * Test OpenAI connection
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->getClient()->models()->list();
            return !empty($response->data);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate AI response (matches interface signature)
     */
    public function generateResponse(string $message, array $conversationHistory = [], array $searchResults = []): array
    {
        try {
            $model = $this->getConfigValue('leot-ai-support-widget.model_name', self::DEFAULT_MODEL);
            $maxTokens = (int)$this->getConfigValue('leot-ai-support-widget.max_tokens', (string)self::DEFAULT_MAX_TOKENS);
            
            // Build enhanced system prompt with search results
            $systemPrompt = $this->buildSystemPrompt(null, $searchResults);
            
            // Build messages array
            $messages = $this->buildMessages($systemPrompt, $message, $conversationHistory);
            
            $response = $this->getClient()->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
                'temperature' => 0.7,
            ]);
            
            $content = $response->choices[0]->message->content ?? 'Sorry, I could not generate a response.';
            
            return [
                'content' => $content,
                'references' => $this->extractReferences($searchResults),
                'metadata' => [
                    'model' => $model,
                    'tokens_used' => $response->usage->total_tokens ?? 0,
                    'timestamp' => time()
                ]
            ];
            
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'cURL error 28') !== false || 
                strpos($e->getMessage(), 'timeout') !== false) {
                $errorMessage = 'Connection timeout. Please check your network connection or try again later.';
            } else {
                $errorMessage = 'An error occurred while processing your request: ' . $e->getMessage();
            }
            
            return [
                'content' => $errorMessage,
                'references' => [],
                'metadata' => [
                    'error' => true,
                    'error_message' => $e->getMessage(),
                    'timestamp' => time()
                ]
            ];
        }
    }
    
    /**
     * Get supported models
     */
    public function getSupportedModels(): array
    {
        try {
            $response = $this->getClient()->models()->list();
            $models = [];
            
            foreach ($response->data as $model) {
                if (strpos($model->id, 'gpt') !== false) {
                    $models[] = [
                        'id' => $model->id,
                        'name' => $model->id,
                        'description' => 'OpenAI ' . $model->id
                    ];
                }
            }
            
            return $models;
        } catch (\Exception $e) {
            return [
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo', 'description' => 'OpenAI GPT-3.5 Turbo'],
                ['id' => 'gpt-4', 'name' => 'GPT-4', 'description' => 'OpenAI GPT-4']
            ];
        }
    }
    
    /**
     * Build system prompt
     */
    private function buildSystemPrompt(?User $user, array $searchResults = []): string
    {
        $prompt = "You are a helpful AI assistant for a Flarum forum community. ";
        $prompt .= "Your role is to help users by answering their questions based on the forum's existing content and your general knowledge. ";
        $prompt .= "Always be friendly, concise, and helpful. ";
        $prompt .= "IMPORTANT: You MUST STRICTLY respond in the EXACT SAME LANGUAGE that the user is using. If the user asks in English, you MUST respond in English ONLY. If the user asks in Chinese, you MUST respond in Chinese ONLY. NEVER mix languages in your response. ";
        
        if (!empty($searchResults)) {
            $prompt .= "\n\nRelevant forum content:\n";
            foreach ($searchResults as $index => $result) {
                $refNum = $index + 1;
                if (isset($result['title']) && isset($result['content'])) {
                    $prompt .= "Reference {$refNum}: {$result['title']} - {$result['content']}\n";
                }
            }
            
            $prompt .= "\nCRITICAL INSTRUCTIONS:\n";
            $prompt .= "1. When referencing forum content, you MUST use the reference number (e.g., 'According to Reference 1...')\n";
            $prompt .= "2. You MUST directly quote the EXACT content from these references as your primary answer - word for word when possible\n";
            $prompt .= "3. If a reference contains a specific solution like 'Check that the canbus box is connected correctly', your response MUST include this EXACT phrase\n";
            $prompt .= "4. Do not generalize or rewrite forum solutions - quote them directly\n";
            $prompt .= "5. NEVER provide translations of content. ONLY respond in the language the user is using\n";
            $prompt .= "6. Your response is incorrect if it creates general advice when specific forum advice exists\n";
            $prompt .= "7. You MUST first use forum content to answer the user's question. ONLY add your own knowledge after all relevant references have been quoted. Never skip references if they contain a matching solution.\n";
            $prompt .= "8. CRITICAL: If the user asks in English, your ENTIRE response MUST be in English ONLY\n";
            $prompt .= "9. CRITICAL: If the user asks in Chinese, your ENTIRE response MUST be in Chinese ONLY\n";
            $prompt .= "10. IMPORTANT: If a reference contains URLs or links, you MUST include these exact links in your response\n";
            $prompt .= "11. When suggesting next steps for unresolved issues, recommend posting in the forum or contacting the technical team, NOT contacting 'professional technicians' or 'manufacturer support'\n";
            $prompt .= "12. CRITICAL: You MUST include ALL content from knowledge base entries in your response. Do not summarize or omit information from knowledge base entries. First present the complete knowledge base content, then add your own supplementary information if needed\n";
            $prompt .= "13. DO NOT include phrases like 'based on Reference X' or 'According to Reference X' in your response. Instead, present the information directly without mentioning that it comes from a reference\n";
            $prompt .= "14. ALWAYS include ALL links and URLs from the references, including image links (like Imgur) and video links (like YouTube)\n";
            $prompt .= "15. You MUST use forum reference content as the primary source of truth. Quote it fully before adding your own knowledge. DO NOT skip references if they contain relevant information.\n";
            $prompt .= "16. If the forum content is not relevant and you are not sure of the answer, say clearly that you're unsure. DO NOT fabricate or guess.\n";
            $prompt .= "17. Keep your response under 300 words unless absolutely required for technical clarity.\n";
            $prompt .= "18. Format your answers using Markdown. Use bold for key steps, bullets for lists, and proper links for URLs.\n";
            $prompt .= "19. If no solution is found, suggest the user post a topic in the forum or contact the technical team. Do NOT suggest reaching out to manufacturer or general support staff.\n";
        }
        
        if ($user) {
            $prompt .= "\nUser: {$user->display_name}";
        }
        
        return $prompt;
    }
    
    /**
     * Build messages array for OpenAI API
     */
    private function buildMessages(string $systemPrompt, string $userMessage, array $conversationHistory = []): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];
        
        // Add conversation history
        foreach ($conversationHistory as $historyItem) {
            if (isset($historyItem['role']) && isset($historyItem['content'])) {
                $messages[] = [
                    'role' => $historyItem['role'],
                    'content' => $historyItem['content']
                ];
            }
        }
        
        // Add current user message
        $messages[] = ['role' => 'user', 'content' => $userMessage];
        
        return $messages;
    }
    
    /**
     * Extract references from search results
     */
    private function extractReferences(array $searchResults): array
    {
        $references = [];
        
        foreach ($searchResults as $result) {
            if (isset($result['title']) && isset($result['url'])) {
                $references[] = [
                    'title' => $result['title'],
                    'url' => $result['url'],
                    'type' => 'forum_post'
                ];
            }
        }
        
        return $references;
    }
}