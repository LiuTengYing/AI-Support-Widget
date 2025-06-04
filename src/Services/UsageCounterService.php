<?php

namespace LeoT\FlarumAiSupport\Services;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Carbon\Carbon;

class UsageCounterService
{
    /**
     * @var ConnectionInterface
     */
    protected $db;
    
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    
    /**
     * @param ConnectionInterface $db
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(ConnectionInterface $db, SettingsRepositoryInterface $settings)
    {
        $this->db = $db;
        $this->settings = $settings;
    }
    
    /**
     * 增加用户使用计数
     *
     * @param int $userId
     * @return bool
     */
    public function increment(int $userId): bool
    {
        try {
            $date = date('Y-m-d');
            $now = Carbon::now();
            $success = false;
            
            // 检查 ai_support_usage 表是否存在
            $hasUsageTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage');
            
            if ($hasUsageTable) {
                // 检查记录是否存在
                $record = $this->db->table('ai_support_usage')
                    ->where('user_id', $userId)
                    ->where('date', $date)
                    ->first();
                
                if ($record) {
                    // 更新现有记录
                    $this->db->table('ai_support_usage')
                        ->where('id', $record->id)
                        ->update([
                            'count' => $record->count + 1,
                            'updated_at' => $now
                        ]);
                } else {
                    // 创建新记录
                    $this->db->table('ai_support_usage')
                        ->insert([
                            'user_id' => $userId,
                            'date' => $date,
                            'count' => 1,
                            'created_at' => $now,
                            'updated_at' => $now
                        ]);
                }
                $success = true;
                // 如果ai_support_usage表存在，我们只使用这个表，不再使用ai_support_usage_limits表
                return $success;
            }
            
            // 只有当ai_support_usage表不存在时，才使用ai_support_usage_limits表
            $hasLimitsTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage_limits');
            
            if ($hasLimitsTable) {
                // 检查记录是否存在
                $record = $this->db->table('ai_support_usage_limits')
                    ->where('user_id', $userId)
                    ->where('date', $date)
                    ->first();
                
                if ($record) {
                    // 更新现有记录
                    $this->db->table('ai_support_usage_limits')
                        ->where('id', $record->id)
                        ->update([
                            'requests_count' => $record->requests_count + 1,
                            'updated_at' => $now
                        ]);
                } else {
                    // 创建新记录
                    $this->db->table('ai_support_usage_limits')
                        ->insert([
                            'user_id' => $userId,
                            'date' => $date,
                            'requests_count' => 1,
                            'created_at' => $now,
                            'updated_at' => $now
                        ]);
                }
                $success = true;
                return $success;
            }
            
            // 如果两个表都不存在，尝试创建 ai_support_usage 表
            if (!$hasUsageTable && !$hasLimitsTable) {
                $this->db->getSchemaBuilder()->create('ai_support_usage', function ($table) {
                    $table->increments('id');
                    $table->unsignedInteger('user_id');
                    $table->date('date');
                    $table->unsignedInteger('count')->default(0);
                    $table->timestamps();
                    $table->index(['user_id', 'date']);
                });
                
                // 创建新记录
                $this->db->table('ai_support_usage')
                    ->insert([
                        'user_id' => $userId,
                        'date' => $date,
                        'count' => 1,
                        'created_at' => $now,
                        'updated_at' => $now
                    ]);
                $success = true;
            }
            
            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 获取用户今日使用次数
     *
     * @param int $userId
     * @return int
     */
    public function getTodayCount(int $userId): int
    {
        try {
            $date = date('Y-m-d');
            $totalCount = 0;
            
            // 检查 ai_support_usage 表
            $hasUsageTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage');
            if ($hasUsageTable) {
                $record = $this->db->table('ai_support_usage')
                    ->where('user_id', $userId)
                    ->where('date', $date)
                    ->first();
                
                if ($record) {
                    $totalCount += $record->count;
                }
                // 如果ai_support_usage表存在，我们只使用这个表
                return $totalCount;
            }
            
            // 只有当ai_support_usage表不存在时，才使用ai_support_usage_limits表
            $hasLimitsTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage_limits');
            if ($hasLimitsTable) {
                $record = $this->db->table('ai_support_usage_limits')
                    ->where('user_id', $userId)
                    ->where('date', $date)
                    ->first();
                
                if ($record) {
                    $totalCount += $record->requests_count;
                }
            }
            
            return $totalCount;
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * 清除过期的使用记录
     *
     * @param int $days 保留天数
     * @return bool
     */
    public function cleanupOldRecords(int $days = 30): bool
    {
        try {
            $date = date('Y-m-d', strtotime("-$days days"));
            $success = false;
            
            // 清理 ai_support_usage 表
            $hasUsageTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage');
            if ($hasUsageTable) {
                $this->db->table('ai_support_usage')
                    ->where('date', '<', $date)
                    ->delete();
                $success = true;
            }
            
            // 清理 ai_support_usage_limits 表
            $hasLimitsTable = $this->db->getSchemaBuilder()->hasTable('ai_support_usage_limits');
            if ($hasLimitsTable) {
                $this->db->table('ai_support_usage_limits')
                    ->where('date', '<', $date)
                    ->delete();
                $success = true;
            }
            
            return $success;
        } catch (\Exception $e) {
            return false;
        }
    }
} 