<?php

namespace LeoT\FlarumAiSupport\Middleware;

use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LogRequestMiddleware implements MiddlewareInterface
{
    /**
     * 处理请求
     * 
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (strpos($path, '/api/ai-support/chat') !== false) {
            // 记录API请求
            $actor = RequestUtil::getActor($request);
            error_log('[AI Support Direct] Processing request: ' . $path . ' from user: ' . $actor->id . ', Is Admin: ' . ($actor->isAdmin() ? 'Yes' : 'No'));
        }
        
        return $handler->handle($request);
    }
} 