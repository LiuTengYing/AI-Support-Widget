<?php

namespace LeoT\FlarumAiSupport\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

class AiUsageStatsSerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'ai-usage-stats';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($model)
    {
        // 确保数据是对象而不是数组
        $data = is_array($model) ? (object)$model : $model;
        
        // 确保即使数据为空也返回有效的结构
        $attributes = [
            'total_usage' => $data->total_usage ?? 0,
            'period' => $data->period ?? 'all',
            'today_usage' => $data->today_usage ?? 0,
            'yesterday_usage' => $data->yesterday_usage ?? 0,
            'active_users' => $data->active_users ?? 0,
            'debug_info' => [
                'time' => date('Y-m-d H:i:s'),
                'has_data' => !empty($data),
                'data_type' => gettype($data),
                'serializer_fixed' => true
            ]
        ];
        
        // 确保用户统计数据被正确序列化
        if (isset($data->user_stats) && is_array($data->user_stats)) {
            $attributes['user_stats'] = $data->user_stats;
        } else {
            $attributes['user_stats'] = [];
        }
        
        // 添加调试信息
        if (isset($data->debug) && is_array($data->debug)) {
            $attributes['debug'] = $data->debug;
        }
        
        return $attributes;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getId($model)
    {
        // 返回一个固定ID，因为这不是一个实体
        return 'ai-usage-stats';
    }
} 