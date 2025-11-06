<?php

namespace app\components\whatsapp;

use yii\base\BaseObject;
use app\components\whatsapp\exceptions\EncryptionException;

class EncryptionStream extends BaseObject implements \Iterator
{
    public $source;
    public $mediaKey;
    public $mediaType;

    private $encryptedData;
    private $position = 0;

    public function init()
    {
        parent::init();

        if (!$this->source) {
            throw new EncryptionException('Source stream is required');
        }

        if (!$this->mediaKey) {
            throw new EncryptionException('Media key is required');
        }

        if (!$this->mediaType) {
            throw new EncryptionException('Media type is required');
        }
    }

    /**
     * Получает зашифрованные данные
     */
    public function getEncryptedData(): string
    {
        if ($this->encryptedData === null) {
            $content = is_string($this->source) ? $this->source : file_get_contents($this->source);
            $this->encryptedData = \Yii::$app->whatsappEncryption->encryptContent($content, $this->mediaKey, $this->mediaType);
        }

        return $this->encryptedData;
    }

    /**
     * Сохраняет зашифрованные данные в файл
     */
    public function saveToFile(string $filename): bool
    {
        $encryptedData = $this->getEncryptedData();
        return file_put_contents($filename, $encryptedData) !== false;
    }

    /**
     * Iterator interface methods
     */
    public function current(): string
    {
        return $this->getEncryptedData()[$this->position] ?? '';
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return $this->position < strlen($this->getEncryptedData());
    }

    /**
     * Дополнительные полезные методы
     */
    public function getSize(): int
    {
        return strlen($this->getEncryptedData());
    }

    public function __toString(): string
    {
        return $this->getEncryptedData();
    }
}