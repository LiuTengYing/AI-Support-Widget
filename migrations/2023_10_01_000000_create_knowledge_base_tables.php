<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // 创建知识库分类表
        if (!$schema->hasTable('kb_categories')) {
            $schema->create('kb_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->text('description')->nullable();
                $table->integer('parent_id')->unsigned()->nullable();
                $table->timestamps();
                
                $table->foreign('parent_id')
                    ->references('id')
                    ->on('kb_categories')
                    ->onDelete('set null');
            });
        }
        
        // 创建知识库条目表
        if (!$schema->hasTable('kb_entries')) {
            $schema->create('kb_entries', function (Blueprint $table) {
                $table->increments('id');
                $table->string('type', 20); // 'qa' 或 'content'
                $table->string('question', 255)->nullable(); // 问答型的问题
                $table->text('answer'); // 答案或内容
                $table->text('keywords')->nullable(); // 用于搜索匹配的关键词
                $table->integer('category_id')->unsigned()->nullable();
                $table->timestamps();
                
                $table->foreign('category_id')
                    ->references('id')
                    ->on('kb_categories')
                    ->onDelete('set null');
                    
                // 添加全文索引
                $table->fullText(['question', 'answer', 'keywords']);
            });
        }
    },
    
    'down' => function (Builder $schema) {
        $schema->dropIfExists('kb_entries');
        $schema->dropIfExists('kb_categories');
    }
]; 