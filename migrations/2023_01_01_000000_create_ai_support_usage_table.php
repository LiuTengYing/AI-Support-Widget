<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 创建AI支持使用记录表
        if (!$schema->hasTable('ai_support_usage')) {
            $schema->create('ai_support_usage', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->date('date');
                $table->unsignedInteger('count')->default(0);
                $table->timestamps();
                
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['user_id', 'date']);
            });
        }
    },
    
    'down' => function (Builder $schema) {
        // 删除表
        $schema->dropIfExists('ai_support_usage');
    }
]; 