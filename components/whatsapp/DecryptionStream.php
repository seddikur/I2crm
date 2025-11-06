<?php

namespace app\components\whatsapp;

use Yii;
use yii\base\BaseObject;
use app\components\whatsapp\exceptions\DecryptionException;

class DecryptionStream extends BaseObject implements \Iterator
{
    public $source;
    public $mediaKey;
    public $mediaType;

    private $decryptedData;
    private $position = 0;

    public function init()
    {
        parent::init();

        if (!$this->source) {
            throw new DecryptionException('Source stream is required');
        }

        if (!$this->mediaKey) {
            throw new DecryptionException('Media key is required');
        }

        if (!$this->mediaType) {
            throw new DecryptionException('Media type is required');
        }
    }

    /**
     * Получает расшифрованные данные
     */
    public function getDecryptedData(): string
    {
        if ($this->decryptedData === null) {
            if (is_string($this->source)) {
                $encryptedContent = $this->source;
                Yii::info('DecryptionStream: source is string, length=' . strlen($encryptedContent), 'whatsapp');
            } else {
                if (!file_exists($this->source)) {
                    throw new DecryptionException('Файл не найден: ' . $this->source);
                }
                
                $fileSize = filesize($this->source);
                Yii::info('DecryptionStream: reading from file=' . $this->source . ', fileSize=' . $fileSize, 'whatsapp');
                
                $encryptedContent = file_get_contents($this->source);
                if ($encryptedContent === false) {
                    throw new DecryptionException('Не удалось прочитать файл: ' . $this->source);
                }
                
                $readSize = strlen($encryptedContent);
                Yii::info('DecryptionStream: readSize=' . $readSize . ', fileSize=' . $fileSize . ', first 32 bytes (hex): ' . bin2hex(substr($encryptedContent, 0, min(32, $readSize))), 'whatsapp');
                
                if ($readSize == 0) {
                    throw new DecryptionException('Файл пуст или не может быть прочитан');
                }
                
                if ($readSize != $fileSize) {
                    throw new DecryptionException('Файл прочитан не полностью. Размер файла: ' . $fileSize . ' байт, прочитано: ' . $readSize . ' байт');
                }
            }
            
            $this->decryptedData = \Yii::$app->whatsappEncryption->decryptContent($encryptedContent, $this->mediaKey, $this->mediaType);
        }

        return $this->decryptedData;
    }

    /**
     * Сохраняет расшифрованные данные в файл
     */
    public function saveToFile(string $filename): bool
    {
        $decryptedData = $this->getDecryptedData();
        return file_put_contents($filename, $decryptedData) !== false;
    }

    /**
     * Iterator interface methods
     */
    public function current(): string
    {
        return $this->getDecryptedData()[$this->position] ?? '';
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
        return $this->position < strlen($this->getDecryptedData());
    }

    /**
     * Дополнительные полезные методы
     */
    public function getSize(): int
    {
        return strlen($this->getDecryptedData());
    }

    public function __toString(): string
    {
        return $this->getDecryptedData();
    }
}