<?php

namespace LeoT\FlarumAiSupport\Model;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int|null $parent_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class KnowledgeBaseCategory extends AbstractModel
{
    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'kb_categories';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'parent_id'
    ];

    /**
     * 获取该分类下的所有条目
     */
    public function entries(): HasMany
    {
        return $this->hasMany(KnowledgeBaseEntry::class, 'category_id');
    }

    /**
     * 获取父分类
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBaseCategory::class, 'parent_id');
    }

    /**
     * 获取子分类
     */
    public function children(): HasMany
    {
        return $this->hasMany(KnowledgeBaseCategory::class, 'parent_id');
    }
} 