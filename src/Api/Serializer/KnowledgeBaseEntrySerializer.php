<?php

namespace LeoT\FlarumAiSupport\Api\Serializer;

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
     *
     * @param KnowledgeBaseEntry $entry
     */
    protected function getDefaultAttributes($entry)
    {
        return [
            'id' => $entry->id,
            'type' => $entry->type,
            'question' => $entry->question,
            'answer' => $entry->answer,
            'keywords' => $entry->keywords,
            'category_id' => $entry->category_id,
            'created_at' => $this->formatDate($entry->created_at),
            'updated_at' => $this->formatDate($entry->updated_at),
        ];
    }
} 