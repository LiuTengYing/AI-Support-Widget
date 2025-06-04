<?php

namespace LeoT\FlarumAiSupport\Formatter;

use Flarum\Formatter\Event\Configuring;
use Illuminate\Events\Dispatcher;
use LeoT\FlarumAiSupport\Api\AiServiceInterface;

class AiBBCodeFormatter
{
    protected $aiService;
    
    public function __construct(AiServiceInterface $aiService)
    {
        $this->aiService = $aiService;
    }
    
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Configuring::class, [$this, 'addAiBBCode']);
    }
    
    public function addAiBBCode(Configuring $event)
    {
        $event->configurator->BBCodes->addCustom(
            '[ai]{TEXT}[/ai]',
            '<div class="ai-quote-block">{@ai_response}</div>'
        );
        
        $event->configurator->rendering->getRenderer()->setParameter(
            'ai_response',
            function($text) {
                return $this->processAiQuery($text);
            }
        );
    }
    
    protected function processAiQuery($question)
    {
        try {
            $response = $this->aiService->generateResponse($question, [], []);
            return '<div class="ai-response">' . 
                   '<div class="ai-response-header">AI Assistant:</div>' .
                   '<div class="ai-response-content">' . htmlspecialchars($response['response']) . '</div>' .
                   '</div>';
        } catch (\Exception $e) {
            return '<div class="ai-error">AI service unavailable</div>';
        }
    }
}