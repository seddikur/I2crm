<?php

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class DecryptForm extends Model
{
    public $file;
    public $mediaKey;
    public $mediaType = 'DOCUMENT';

    public function rules()
    {
        return [
            [['file', 'mediaKey', 'mediaType'], 'required'],
            ['file', 'file', 'maxSize' => 50 * 1024 * 1024],
            ['mediaType', 'in', 'range' => ['IMAGE', 'VIDEO', 'AUDIO', 'DOCUMENT']]
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => 'Зашифрованный файл',
            'mediaKey' => 'Ключ (base64)',
            'mediaType' => 'Тип медиа'
        ];
    }
}