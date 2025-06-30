<?php

namespace LeoT\FlarumAiSupport\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiTestController implements RequestHandlerInterface
{
    protected $settings;
    protected $client;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
        $this->client = $this->createHttpClient();
    }
    
    /**
     * Create HTTP client with proxy support
     */
    private function createHttpClient(): Client
    {
        $config = [
            'timeout' => 30,
            'connect_timeout' => 10,
        ];
        
        // Check if proxy is enabled
        $useProxy = $this->settings->get('leot-ai-support-widget.openai_use_proxy', false);
        if ($useProxy) {
            $proxyHost = $this->settings->get('leot-ai-support-widget.proxy_host', '127.0.0.1');
            $proxyPort = $this->settings->get('leot-ai-support-widget.proxy_port', '7890');
            $config['proxy'] = "http://{$proxyHost}:{$proxyPort}";
        }
        
        return new Client($config);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        
        if (!$actor->isAdmin()) {
            return new JsonResponse(['error' => 'Admin access required'], 403);
        }

        $provider = $this->settings->get('leot-ai-support-widget.provider', 'openai');
        $apiKey = $this->settings->get('leot-ai-support-widget.api_key');
        
        if (empty($apiKey)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'API key not configured',
                'provider' => $provider
            ]);
        }

        $testResults = [];
        
        try {
            if ($provider === 'deepseek') {
                $testResults = $this->testDeepSeekApi($apiKey);
            } else {
                $testResults = $this->testOpenAiApi($apiKey);
            }
            
            return new JsonResponse([
                'success' => true,
                'provider' => $provider,
                'results' => $testResults
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function testDeepSeekApi($apiKey)
    {
        $results = [];
        
        // Test 1: Check API endpoint accessibility
        try {
            $start = microtime(true);
            $response = $this->client->get('https://api.deepseek.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);
            $duration = round((microtime(true) - $start) * 1000, 2);
            
            $results['models_endpoint'] = [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'message' => 'Models endpoint accessible'
            ];
        } catch (RequestException $e) {
            $results['models_endpoint'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to access models endpoint'
            ];
        }
        
        // Test 2: Simple chat completion
        try {
            $start = microtime(true);
            $response = $this->client->post('https://api.deepseek.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'deepseek-chat',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Hello, this is a test message. Please respond with "API test successful".'
                        ]
                    ],
                    'max_tokens' => 50,
                    'temperature' => 0.1
                ]
            ]);
            $duration = round((microtime(true) - $start) * 1000, 2);
            
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $results['chat_completion'] = [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'response_content' => $responseData['choices'][0]['message']['content'] ?? 'No content',
                'message' => 'Chat completion successful'
            ];
        } catch (RequestException $e) {
            $results['chat_completion'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Chat completion failed'
            ];
        }
        
        return $results;
    }
    
    private function testOpenAiApi($apiKey)
    {
        $results = [];
        
        // Test 1: Check API endpoint accessibility
        try {
            $start = microtime(true);
            $response = $this->client->get('https://api.openai.com/v1/models', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ]
            ]);
            $duration = round((microtime(true) - $start) * 1000, 2);
            
            $results['models_endpoint'] = [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'message' => 'Models endpoint accessible'
            ];
        } catch (RequestException $e) {
            $results['models_endpoint'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to access models endpoint'
            ];
        }
        
        // Test 2: Simple chat completion
        try {
            $start = microtime(true);
            $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => 'Hello, this is a test message. Please respond with "API test successful".'
                        ]
                    ],
                    'max_tokens' => 50,
                    'temperature' => 0.1
                ]
            ]);
            $duration = round((microtime(true) - $start) * 1000, 2);
            
            $responseData = json_decode($response->getBody()->getContents(), true);
            
            $results['chat_completion'] = [
                'success' => true,
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $duration,
                'response_content' => $responseData['choices'][0]['message']['content'] ?? 'No content',
                'message' => 'Chat completion successful'
            ];
        } catch (RequestException $e) {
            $results['chat_completion'] = [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Chat completion failed'
            ];
        }
        
        return $results;
    }
}