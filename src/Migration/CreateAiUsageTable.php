<?php

namespace LeoT\FlarumAiSupport\Migration;

use Flarum\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

return Migration::createTable('ai_support_usage', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id');
    $table->date('date');
    $table->unsignedInteger('count')->default(1);
    
    // 外键约束
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    
    // 复合唯一索引，确保每个用户每天只有一条记录
    $table->unique(['user_id', 'date']);
}); 