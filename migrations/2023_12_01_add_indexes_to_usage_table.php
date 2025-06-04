<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 检查表是否存在
        if ($schema->hasTable('ai_support_usage_limits')) {
            $schema->table('ai_support_usage_limits', function (Blueprint $table) {
                // 添加复合索引，优化按用户ID和日期查询的性能
                $table->index(['user_id', 'date'], 'ai_support_user_date_index');
                
                // 添加日期索引，优化按日期查询的性能
                $table->index('date', 'ai_support_date_index');
            });
        }
    },
    
    'down' => function (Builder $schema) {
        if ($schema->hasTable('ai_support_usage_limits')) {
            $schema->table('ai_support_usage_limits', function (Blueprint $table) {
                // 删除添加的索引
                $table->dropIndex('ai_support_user_date_index');
                $table->dropIndex('ai_support_date_index');
            });
        }
    }
];