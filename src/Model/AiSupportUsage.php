<?php

namespace LeoT\FlarumAiSupport\Model;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

class AiSupportUsage extends AbstractModel
{
    protected $table = 'ai_support_usage_limits';
    
    protected $fillable = ['user_id', 'requests_count', 'date'];
    
    /**
     * 关联到用户
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * 获取或创建用户今天的使用记录
     */
    public static function getOrCreateTodayRecord($userId)
    {
        $today = date('Y-m-d');
        
        $record = static::where('user_id', $userId)
            ->where('date', $today)
            ->first();
            
        if (!$record) {
            $record = static::create([
                'user_id' => $userId,
                'requests_count' => 0,
                'date' => $today
            ]);
        }
        
        return $record;
    }
    
    /**
     * 增加用户的使用次数
     */
    public static function incrementUsage($userId)
    {
        $record = static::getOrCreateTodayRecord($userId);
        $record->requests_count += 1;
        $record->save();
        
        return $record;
    }
    
    /**
     * 批量记录用户使用情况
     * 用于批量处理多个用户的使用记录，减少数据库操作次数
     */
    public static function batchIncrementUsage(array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }
        
        $today = date('Y-m-d');
        $records = [];
        
        // 获取所有已存在的记录
        $existingRecords = static::whereIn('user_id', $userIds)
            ->where('date', $today)
            ->get()
            ->keyBy('user_id');
        
        // 批量更新或创建记录
        foreach ($userIds as $userId) {
            if (isset($existingRecords[$userId])) {
                // 更新已存在的记录
                $record = $existingRecords[$userId];
                $record->requests_count += 1;
                $records[] = $record;
            } else {
                // 创建新记录
                $records[] = new static([
                    'user_id' => $userId,
                    'requests_count' => 1,
                    'date' => $today
                ]);
            }
        }
        
        // 批量保存
        foreach ($records as $record) {
            $record->save();
        }
        
        return $records;
    }
    
    /**
     * 检查用户是否已达到今日限制
     */
    public static function hasReachedLimit($user, $limit)
    {
        // 如果限制设置为0，表示无限制
        if ($limit <= 0) {
            return false;
        }
        
        // 管理员不受限制
        if ($user->isAdmin()) {
            return false;
        }
        
        // 版主不受限制（检查是否有管理帖子的权限）
        if ($user->hasPermission('discussion.moderate')) {
            return false;
        }
        
        $record = static::getOrCreateTodayRecord($user->id);
        return $record->requests_count >= $limit;
    }
}