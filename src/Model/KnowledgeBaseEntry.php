<?php

namespace LeoT\FlarumAiSupport\Model;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $type
 * @property string|null $question
 * @property string $answer
 * @property string|null $keywords
 * @property int|null $category_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KnowledgeBaseEntry extends AbstractModel
{
    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'kb_entries';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'question',
        'answer',
        'keywords',
        'category_id'
    ];

    /**
     * 类型常量
     */
    const TYPE_QA = 'qa'; // 问答型
    const TYPE_CONTENT = 'content'; // 话术型

    /**
     * 获取所属分类
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id');
    }
} 