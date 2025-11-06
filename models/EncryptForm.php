<?php

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class EncryptForm extends Model
{
    public $file;
    public $mediaType = 'DOCUMENT';

    public function rules()
    {
        return [
            [['file', 'mediaType'], 'required'],
            ['file', 'file', 'maxSize' => 50 * 1024 * 1024], // 50MB
            ['mediaType', 'in', 'range' => ['IMAGE', 'VIDEO', 'AUDIO', 'DOCUMENT']]
        ];
    }

    public function attributeLabels()
    {
        return [
            'file' => 'Файл',
            'mediaType' => 'Тип медиа'
        ];
    }
}