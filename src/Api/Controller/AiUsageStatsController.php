<?php

namespace LeoT\FlarumAiSupport\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\User\User;
use Illuminate\Support\Arr;
use LeoT\FlarumAiSupport\Api\Serializer\AiUsageStatsSerializer;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Capsule\Manager as DB;
use Tobscure\JsonApi\Document;

class AiUsageStatsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = AiUsageStatsSerializer::class;

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        // 确保用户有权限查看统计信息
        if (!$actor->isAdmin()) {
            return [];
        }

        // 获取请求参数
        $queryParams = $request->getQueryParams();
        $period = Arr::get($queryParams, 'period', 'all');

        // 获取统计数据
        $stats = $this->getStats($period);
        
        // 获取用户统计数据
        $userStats = $this->getUserStats($period);
        
        // 将用户统计数据放在meta中
        $document->setMeta([
            'user_stats' => $userStats
        ]);
        
        return [$stats];
    }

    /**
     * 获取AI使用统计数据
     */
    private function getStats(string $period)
    {
        $stats = new \stdClass();
        $stats->period = $period;
        
        try {
            // 查询总使用次数
            $totalUsage = 0;
            
            // 从 ai_support_usage 表获取数据
            if (DB::getSchemaBuilder()->hasTable('ai_support_usage')) {
                $query = DB::table('ai_support_usage');
                $this->applyPeriodFilter($query, $period);
                $usageCount = $query->sum('count');
                $totalUsage += $usageCount ?: 0;
                
                $stats->total_usage = $totalUsage;
                
                // 今日使用次数
                $today = date('Y-m-d');
                $todayUsage = DB::table('ai_support_usage')
                    ->whereDate('date', $today)
                    ->sum('count') ?: 0;
                
                $stats->today_usage = $todayUsage;
                
                // 昨日使用次数
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $yesterdayUsage = DB::table('ai_support_usage')
                    ->whereDate('date', $yesterday)
                    ->sum('count') ?: 0;
                
                $stats->yesterday_usage = $yesterdayUsage;
                
                // 活跃用户数
                $query = DB::table('ai_support_usage')
                    ->select('user_id')
                    ->distinct();
                $this->applyPeriodFilter($query, $period);
                $activeUsers = $query->pluck('user_id')->all();
                
                $stats->active_users = count(array_unique($activeUsers));
                
                return $stats;
            }
            
            // 只有当ai_support_usage表不存在时，才使用ai_support_usage_limits表
            if (DB::getSchemaBuilder()->hasTable('ai_support_usage_limits')) {
                $query = DB::table('ai_support_usage_limits');
                $this->applyPeriodFilter($query, $period);
                $limitsCount = $query->sum('requests_count');
                $totalUsage += $limitsCount ?: 0;
                
                $stats->total_usage = $totalUsage;
                
                // 今日使用次数
                $today = date('Y-m-d');
                $todayUsage = DB::table('ai_support_usage_limits')
                    ->whereDate('date', $today)
                    ->sum('requests_count') ?: 0;
                
                $stats->today_usage = $todayUsage;
                
                // 昨日使用次数
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $yesterdayUsage = DB::table('ai_support_usage_limits')
                    ->whereDate('date', $yesterday)
                    ->sum('requests_count') ?: 0;
                
                $stats->yesterday_usage = $yesterdayUsage;
                
                // 活跃用户数
                $query = DB::table('ai_support_usage_limits')
                    ->select('user_id')
                    ->distinct();
                $this->applyPeriodFilter($query, $period);
                $activeUsers = $query->pluck('user_id')->all();
                
                $stats->active_users = count(array_unique($activeUsers));
                
                return $stats;
            }
            
            // 如果两个表都不存在
            $stats->total_usage = 0;
            $stats->today_usage = 0;
            $stats->yesterday_usage = 0;
            $stats->active_users = 0;
            
        } catch (\Exception $e) {
            // 如果发生错误，使用默认值
            $stats->total_usage = 0;
            $stats->today_usage = 0;
            $stats->yesterday_usage = 0;
            $stats->active_users = 0;
        }
        
        return $stats;
    }
    
    /**
     * 获取用户AI使用统计数据
     */
    private function getUserStats(string $period)
    {
        try {
            // 存储用户统计数据
            $userStats = [];
            
            // 从 ai_support_usage 表获取数据
            if (DB::getSchemaBuilder()->hasTable('ai_support_usage')) {
                $query = DB::table('ai_support_usage')
                    ->select(
                        'user_id',
                        DB::raw('SUM(count) as total_count'),
                        DB::raw('MAX(date) as last_used')
                    )
                    ->groupBy('user_id');
                
                // 根据时间段筛选
                $this->applyPeriodFilter($query, $period);
                
                // 获取结果
                $results = $query->get();
                
                // 处理结果
                foreach ($results as $result) {
                    $userStats[$result->user_id] = [
                        'user_id' => $result->user_id,
                        'total_count' => (int)$result->total_count,
                        'last_used' => $result->last_used
                    ];
                }
                
                // 获取用户信息
                $userIds = array_keys($userStats);
                $users = User::whereIn('id', $userIds)->get()->keyBy('id');
                
                // 格式化结果
                $formattedResults = [];
                foreach ($userStats as $userId => $stat) {
                    $user = $users->get($userId);
                    if (!$user) {
                        continue;
                    }
                    
                    $formattedResults[] = [
                        'user_id' => (int)$userId,
                        'username' => $user->username,
                        'display_name' => $user->display_name,
                        'total_count' => (int)$stat['total_count'],
                        'last_used' => $stat['last_used']
                    ];
                }
                
                return $formattedResults;
            }
            
            // 只有当ai_support_usage表不存在时，才使用ai_support_usage_limits表
            if (DB::getSchemaBuilder()->hasTable('ai_support_usage_limits')) {
                $query = DB::table('ai_support_usage_limits')
                    ->select(
                        'user_id',
                        DB::raw('SUM(requests_count) as total_count'),
                        DB::raw('MAX(date) as last_used')
                    )
                    ->groupBy('user_id');
                
                // 根据时间段筛选
                $this->applyPeriodFilter($query, $period);
                
                // 获取结果
                $results = $query->get();
                
                // 处理结果
                foreach ($results as $result) {
                    $userStats[$result->user_id] = [
                        'user_id' => $result->user_id,
                        'total_count' => (int)$result->total_count,
                        'last_used' => $result->last_used
                    ];
                }
                
                // 获取用户信息
                $userIds = array_keys($userStats);
                $users = User::whereIn('id', $userIds)->get()->keyBy('id');
                
                // 格式化结果
                $formattedResults = [];
                foreach ($userStats as $userId => $stat) {
                    $user = $users->get($userId);
                    if (!$user) {
                        continue;
                    }
                    
                    $formattedResults[] = [
                        'user_id' => (int)$userId,
                        'username' => $user->username,
                        'display_name' => $user->display_name,
                        'total_count' => (int)$stat['total_count'],
                        'last_used' => $stat['last_used']
                    ];
                }
                
                return $formattedResults;
            }
            
            // 如果两个表都不存在，返回空数组
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 根据时间段筛选查询
     */
    private function applyPeriodFilter($query, string $period)
    {
        switch ($period) {
            case 'today':
                $query->whereDate('date', date('Y-m-d'));
                break;
            case 'yesterday':
                $query->whereDate('date', date('Y-m-d', strtotime('-1 day')));
                break;
            case 'week':
                $query->where('date', '>=', date('Y-m-d', strtotime('-7 days')));
                break;
            case 'month':
                $query->where('date', '>=', date('Y-m-d', strtotime('-30 days')));
                break;
            // 'all' 或其他情况不需要筛选
        }
        
        return $query;
    }
} 