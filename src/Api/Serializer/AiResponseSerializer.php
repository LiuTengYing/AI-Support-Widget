<?php

namespace LeoT\FlarumAiSupport\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;

class AiResponseSerializer extends AbstractSerializer
{
    /**
     * 获取资源类型
     *
     * @var string
     */
    protected $type = 'ai-responses';
    
    /**
     * 获取资源ID
     *
     * @param object $model
     * @return string
     */
    public function getId($model)
    {
        return isset($model->id) ? $model->id : '1';
    }

    /**
     * 序列化模型
     *
     * @param object $model
     * @return array
     */
    protected function getDefaultAttributes($model)
    {
        return [
            'response' => $model->response ?? '',
            'references' => $model->references ?? []
        ];
    }
} 