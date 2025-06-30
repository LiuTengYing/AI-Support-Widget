<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        // Create AI conversation log table (optional feature)
        $schema->create('ai_support_conversations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('session_id', 100)->index();
            $table->text('user_message');
            $table->text('ai_response');
            $table->json('references')->nullable(); // Referenced post information
            $table->integer('tokens_used')->default(0);
            $table->string('model_used', 50)->nullable();
            $table->enum('rating', ['good', 'bad'])->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['user_id', 'created_at']);
            $table->index('session_id');
        });

        // Create AI support statistics table
        $schema->create('ai_support_stats', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->integer('total_conversations')->default(0);
            $table->integer('total_tokens_used')->default(0);
            $table->integer('good_ratings')->default(0);
            $table->integer('bad_ratings')->default(0);
            $table->json('provider_usage')->nullable(); // Usage statistics by provider
            $table->timestamps();
            
            $table->unique('date');
        });
    },
    
    'down' => function (Builder $schema) {
        $schema->dropIfExists('ai_support_conversations');
        $schema->dropIfExists('ai_support_stats');
    }
];
