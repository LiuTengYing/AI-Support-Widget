<?php

namespace LeoT\FlarumAiSupport\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseCategory;

class KnowledgeBaseCategorySerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'kb-categories';

    /**
     * {@inheritdoc}
     *
     * @param KnowledgeBaseCategory $category
     */
    protected function getDefaultAttributes($category)
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
            'created_at' => $this->formatDate($category->created_at),
            'updated_at' => $this->formatDate($category->updated_at),
        ];
    }
} 