<?php

namespace LeoT\FlarumAiSupport\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\JsonResponse;

class HandleErrorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Exception $e) {
            // 记录详细错误日志
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            $errorCode = $e->getCode() ?: 500;
            $requestPath = $request->getUri()->getPath();
            
            // 记录结构化错误日志
            error_log(sprintf(
                "[AI Support Widget] Error in %s: %s\nCode: %d\nTrace: %s",
                $requestPath,
                $errorMessage,
                $errorCode,
                $errorTrace
            ));
            
            // 根据错误类型返回适当的HTTP状态码
            $statusCode = $errorCode >= 400 && $errorCode < 600 ? $errorCode : 500;
            
            // 返回友好的JSON错误响应，但不暴露内部错误细节
            return new JsonResponse([
                'success' => false,
                'error' => $errorMessage,
                'error_type' => get_class($e),
                'request_path' => $requestPath
            ], $statusCode);
        }
    }
}