<?php

namespace app\models;

use yii\base\Model;

class ShortLinkForm extends Model
{
    public string $url = '';

    public function rules(): array
    {
        return [
            [['url'], 'trim'],
            [['url'], 'required'],
            [['url'], 'string', 'max' => 2048],
            [['url'], 'url', 'validSchemes' => ['http', 'https']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'url' => 'URL',
        ];
    }
}
