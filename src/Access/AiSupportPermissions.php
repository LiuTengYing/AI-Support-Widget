<?php

namespace LeoT\FlarumAiSupport\Access;

use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Group\Permission;
use Illuminate\Database\Capsule\Manager as DB;
use Flarum\Group\Group;
use Flarum\Database\AbstractModel;

class AiSupportPermissions extends AbstractServiceProvider
{
    public function boot()
    {
        // 这里不使用事件监听，只在首次安装插件时设置默认权限
        try {
            // 检查权限是否已存在
            $exists = Permission::query()
                ->where('permission', 'leot-flarum-ai-support-widget.use')
                ->exists();
            
            if (!$exists) {
                // 添加管理员组权限作为默认设置
                Permission::query()->insert([
                    'group_id' => Group::ADMINISTRATOR_ID,
                    'permission' => 'leot-flarum-ai-support-widget.use',
                ]);
            }
        } catch (\Exception $e) {
            // 记录错误但不中断应用启动
            error_log('注册AI支持权限失败: ' . $e->getMessage());
        }
    }

    public function register()
    {
        // 注册服务...
    }

    /**
     * 检查用户是否有权使用AI支持
     */
    public static function canUseAiSupport($actor)
    {
        return $actor->hasPermission('leot-flarum-ai-support-widget.use');
    }
} 