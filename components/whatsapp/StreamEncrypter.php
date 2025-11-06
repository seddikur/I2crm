<?php

namespace app\components\whatsapp;

use Yii;
use yii\base\Component;
use app\components\whatsapp\exceptions\EncryptionException;
use app\components\whatsapp\exceptions\DecryptionException;

class StreamEncrypter extends Component
{
    public const MEDIA_TYPES = [
        'IMAGE' => 'WhatsApp Image Keys',
        'VIDEO' => 'WhatsApp Video Keys',
        'AUDIO' => 'WhatsApp Audio Keys',
        'DOCUMENT' => 'WhatsApp Document Keys'
    ];

    public const CHUNK_SIZE = 64 * 1024; // 64KB

    /**
     * Расширяет mediaKey используя HKDF
     */
    public function expandMediaKey(string $mediaKey, string $mediaType): array
    {
        if (!isset(self::MEDIA_TYPES[$mediaType])) {
            throw new EncryptionException("Неподдерживаемый тип медиа: {$mediaType}");
        }

        if (strlen($mediaKey) !== 32) {
            throw new EncryptionException("Media key должен быть 32 байта");
        }

        $info = self::MEDIA_TYPES[$mediaType];
        Yii::info('expandMediaKey: mediaType=' . $mediaType . ', info=' . $info . ', mediaKey length=' . strlen($mediaKey), 'whatsapp');
        Yii::info('expandMediaKey: mediaKey (hex)=' . bin2hex($mediaKey), 'whatsapp');
        
        $mediaKeyExpanded = $this->hkdf($mediaKey, 112, $info);
        Yii::info('expandMediaKey: expanded length=' . strlen($mediaKeyExpanded), 'whatsapp');

        $keys = [
            'iv' => substr($mediaKeyExpanded, 0, 16),
            'cipherKey' => substr($mediaKeyExpanded, 16, 32),
            'macKey' => substr($mediaKeyExpanded, 48, 32),
            'refKey' => substr($mediaKeyExpanded, 80, 32)
        ];
        
        Yii::info('expandMediaKey: macKey (hex)=' . bin2hex($keys['macKey']), 'whatsapp');

        return $keys;
    }

    /**
     * Шифрует содержимое файла
     */
    public function encryptContent(string $content, string $mediaKey, string $mediaType): string
    {
        $keys = $this->expandMediaKey($mediaKey, $mediaType);

        // Генерируем случайный IV
        $iv = Yii::$app->security->generateRandomKey(16);

        // PKCS7 padding
        $blockSize = 16;
        $padding = $blockSize - (strlen($content) % $blockSize);
        $content .= str_repeat(chr($padding), $padding);

        // Шифрование
        $encrypted = openssl_encrypt($content, 'AES-256-CBC', $keys['cipherKey'], OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new EncryptionException('Ошибка шифрования');
        }

        // Вычисление MAC
        $mac = hash_hmac('sha256', $iv . $encrypted, $keys['macKey'], true);
        $mac = substr($mac, 0, 10);

        return $iv . $encrypted . $mac;
    }

    /**
     * Дешифрует содержимое файла
     */
    public function decryptContent(string $encryptedData, string $mediaKey, string $mediaType): string
    {
        $keys = $this->expandMediaKey($mediaKey, $mediaType);

        $dataSize = strlen($encryptedData);
        Yii::info('DecryptContent: dataSize=' . $dataSize . ', mediaType=' . $mediaType . ', minRequired=26', 'whatsapp');
        
        if ($dataSize < 26) { // min IV(16) + 1 block + MAC(10)
            Yii::error('DecryptContent: Данные слишком короткие. Размер: ' . $dataSize . ' байт, требуется минимум 26 байт', 'whatsapp');
            throw new DecryptionException('Зашифрованные данные слишком короткие. Размер: ' . $dataSize . ' байт, требуется минимум 26 байт');
        }

        // Извлекаем компоненты
        $iv = substr($encryptedData, 0, 16);
        $file = substr($encryptedData, 16, -10);
        $mac = substr($encryptedData, -10);

        Yii::info('DecryptContent: IV length=' . strlen($iv) . ', encrypted data length=' . strlen($file) . ', MAC length=' . strlen($mac), 'whatsapp');
        Yii::info('DecryptContent: IV (hex)=' . bin2hex($iv), 'whatsapp');
        Yii::info('DecryptContent: MAC from file (hex)=' . bin2hex($mac), 'whatsapp');

        // Проверяем MAC
        $computedMac = hash_hmac('sha256', $iv . $file, $keys['macKey'], true);
        $computedMac = substr($computedMac, 0, 10);
        
        Yii::info('DecryptContent: computed MAC (hex)=' . bin2hex($computedMac), 'whatsapp');
        Yii::info('DecryptContent: MAC match=' . (hash_equals($computedMac, $mac) ? 'true' : 'false'), 'whatsapp');

        if (!hash_equals($computedMac, $mac)) {
            Yii::error('DecryptContent: MAC mismatch. Expected: ' . bin2hex($mac) . ', Got: ' . bin2hex($computedMac), 'whatsapp');
            throw new DecryptionException('Ошибка проверки MAC. Ключ не подходит к этому файлу. Проверьте: 1) Используете ли вы тот же mediaKey, который был возвращен при шифровании; 2) Правильно ли выбран тип медиа (IMAGE/VIDEO/AUDIO/DOCUMENT); 3) Если файл из samples, используйте соответствующий .key файл');
        }

        // Дешифруем
        $decrypted = openssl_decrypt($file, 'AES-256-CBC', $keys['cipherKey'], OPENSSL_RAW_DATA, $iv);

        if ($decrypted === false) {
            throw new DecryptionException('Ошибка дешифрования');
        }

        // Убираем padding
        $padding = ord($decrypted[strlen($decrypted) - 1]);
        $decrypted = substr($decrypted, 0, -$padding);

        return $decrypted;
    }

    /**
     * Генерирует sidecar для стриминга
     */
    public function generateSideCar(string $content, string $mediaKey, string $mediaType): string
    {
        $keys = $this->expandMediaKey($mediaKey, $mediaType);
        $sidecar = '';

        $offset = 0;
        $contentLength = strlen($content);

        while ($offset < $contentLength) {
            $chunk = substr($content, $offset, self::CHUNK_SIZE);

            // Подписываем chunk + 16 байт из следующего chunk'а если есть
            $nextBytes = substr($content, $offset + strlen($chunk), 16);
            $dataToSign = $chunk . $nextBytes;

            $mac = hash_hmac('sha256', $dataToSign, $keys['macKey'], true);
            $sidecar .= substr($mac, 0, 10);

            $offset += strlen($chunk);
        }

        return $sidecar;
    }

    /**
     * HKDF implementation
     */
    private function hkdf(string $key, int $length, string $info = ''): string
    {
        $hashLength = 32; // SHA-256
        $t = '';
        $last = '';
        $n = ceil($length / $hashLength);

        for ($i = 1; $i <= $n; $i++) {
            $last = hash_hmac('sha256', $last . $info . chr($i), $key, true);
            $t .= $last;
        }

        return substr($t, 0, $length);
    }
}