<?php

namespace LeoT\FlarumAiSupport\Api;

use Flarum\User\User;

interface AiServiceInterface
{
    /**
     * 获取AI响应
     *
     * @param string $message 用户消息
     * @param User $user 当前用户
     * @param array $conversationHistory 对话历史
     * @return string AI响应文本
     */
    public function getResponse(string $message, User $user, array $conversationHistory = []): string;
    
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
    public function getResponseWithContext(string $message, User $user, string $systemPrompt, array $searchResults, array $conversationHistory = []): string;
    
    /**
     * 测试API连接
     *
     * @return bool 连接是否成功
     */
    public function testConnection(): bool;
    
    /**
     * Generate AI response based on user message and context
     *
     * @param string $message User's message
     * @param array $conversationHistory Previous conversation history
     * @param array $searchResults Related forum posts
     * @return array Response data including content, references, and metadata
     */
    public function generateResponse(string $message, array $conversationHistory = [], array $searchResults = []): array;
    
    /**
     * Get supported models for this provider
     *
     * @return array List of available models
     */
    public function getSupportedModels(): array;
}
