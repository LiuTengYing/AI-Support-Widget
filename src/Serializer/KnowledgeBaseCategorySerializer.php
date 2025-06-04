<?php

namespace LeoT\FlarumAiSupport\Serializer;

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
     */
    protected function getDefaultAttributes($category)
    {
        if (!($category instanceof KnowledgeBaseCategory)) {
            return [];
        }

        return [
            'id' => $category->id,
            'name' => $category->name,
            'description' => $category->description,
            'created_at' => $this->formatDate($category->created_at),
            'updated_at' => $this->formatDate($category->updated_at)
        ];
    }
}