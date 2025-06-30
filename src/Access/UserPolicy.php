<?php

namespace LeoT\FlarumAiSupport\Access;

use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use Flarum\Settings\SettingsRepositoryInterface;

class UserPolicy extends AbstractPolicy
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * 检查用户是否有权使用AI支持
     * 
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function aiSupportUse(User $actor)
    {
        if ($actor->hasPermission('leot-ai-support-widget.use')) {
            return $this->allow();
        }
        
        return $this->deny();
    }

    public function useAiSupport(User $actor, User $user)
    {
        // 用户只能使用自己的AI支持
        if ($actor->id !== $user->id) {
            return $this->deny();
        }

        // 禁止游客（未注册用户）使用AI支持功能
        if ($actor->isGuest()) {
            return $this->deny();
        }
        
        // 检查用户是否有权限
        if ($actor->can('leot-ai-support-widget.use')) {
            return $this->allow();
        }
        
        return $this->deny();
    }

    /**
     * 用户是否可以查看知识库
     *
     * @param User $actor
     * @return bool
     */
    public function viewKnowledgeBase(User $actor)
    {
        return $actor->hasPermission('leot-ai-support-widget.use');
    }

    /**
     * 用户是否可以创建知识库条目
     *
     * @param User $actor
     * @return bool
     */
    public function createKnowledgeBase(User $actor)
    {
        return $actor->hasPermission('leot-ai-support-widget.manageKnowledgeBase') || $actor->isAdmin();
    }

    /**
     * 用户是否可以编辑知识库条目
     *
     * @param User $actor
     * @return bool
     */
    public function editKnowledgeBase(User $actor)
    {
        return $actor->hasPermission('leot-ai-support-widget.manageKnowledgeBase') || $actor->isAdmin();
    }

    /**
     * 用户是否可以删除知识库条目
     *
     * @param User $actor
     * @return bool
     */
    public function deleteKnowledgeBase(User $actor)
    {
        return $actor->hasPermission('leot-ai-support-widget.manageKnowledgeBase') || $actor->isAdmin();
    }
} 