<?php

namespace LeoT\FlarumAiSupport\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use LeoT\FlarumAiSupport\Model\KnowledgeBaseEntry;

class KnowledgeBaseEntrySerializer extends AbstractSerializer
{
    /**
     * {@inheritdoc}
     */
    protected $type = 'kb-entries';

    /**
     * {@inheritdoc}
     */
    protected function getDefaultAttributes($entry)
    {
        if (!($entry instanceof KnowledgeBaseEntry)) {
            return [];
        }

        $attributes = [
            'id' => $entry->id,
            'type' => $entry->type,
            'question' => $entry->question,
            'answer' => $entry->answer,
            'content' => $entry->answer,
            'keywords' => $entry->keywords,
            'category_id' => $entry->category_id,
            'created_at' => $this->formatDate($entry->created_at),
            'updated_at' => $this->formatDate($entry->updated_at)
        ];
        
        if ($entry->type === 'content') {
            $attributes['title'] = $entry->question;
        }
        
        return $attributes;
    }
} 