<?php

namespace app\components\whatsapp;

use yii\base\BaseObject;

class SideCarGenerator extends BaseObject
{
    public $source;
    public $mediaKey;
    public $mediaType;

    private $sidecarData;

    public function init()
    {
        parent::init();

        if (!$this->source) {
            throw new \InvalidArgumentException('Source is required');
        }

        if (!$this->mediaKey) {
            throw new \InvalidArgumentException('Media key is required');
        }

        if (!$this->mediaType) {
            throw new \InvalidArgumentException('Media type is required');
        }
    }

    /**
     * Генерирует sidecar данные
     */
    public function generate(): string
    {
        if ($this->sidecarData === null) {
            $content = is_string($this->source) ? $this->source : file_get_contents($this->source);
            $this->sidecarData = \Yii::$app->whatsappEncryption->generateSideCar($content, $this->mediaKey, $this->mediaType);
        }

        return $this->sidecarData;
    }

    /**
     * Сохраняет sidecar в файл
     */
    public function saveToFile(string $filename): bool
    {
        $sidecarData = $this->generate();
        return file_put_contents($filename, $sidecarData) !== false;
    }

    public function __toString(): string
    {
        return $this->generate();
    }
}