<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;
use app\models\EncryptForm;
use app\models\DecryptForm;
use app\components\whatsapp\EncryptionStream;
use app\components\whatsapp\DecryptionStream;
use app\components\whatsapp\SideCarGenerator;

class WhatsAppController extends Controller
{

    /**
     * Главная страница с формами
     */
    public function actionIndex()
    {
        $encryptModel = new EncryptForm();
        $decryptModel = new DecryptForm();

        return $this->render('index', [
            'encryptModel' => $encryptModel,
            'decryptModel' => $decryptModel,
        ]);
    }

    /**
     * Шифрование загруженного файла
     */
    public function actionEncrypt()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $encryptModel = new EncryptForm();
        $encryptModel->load(Yii::$app->request->post());
        $encryptModel->file = UploadedFile::getInstance($encryptModel, 'file');

        if (!$encryptModel->validate()) {
            return ['error' => 'Ошибка валидации: ' . implode(', ', $encryptModel->getFirstErrors())];
        }

        try {
            // Генерируем mediaKey
            $mediaKey = Yii::$app->security->generateRandomKey(32);

            // Шифруем файл
            $encryptionStream = new EncryptionStream([
                'source' => $encryptModel->file->tempName,
                'mediaKey' => $mediaKey,
                'mediaType' => $encryptModel->mediaType
            ]);

            $encryptedFilename = Yii::getAlias('@runtime/' . uniqid() . '_' . $encryptModel->file->name . '.encrypted');
            $encryptionStream->saveToFile($encryptedFilename);

            // Сохраняем ключ
            $keyFilename = Yii::getAlias('@runtime/' . uniqid() . '_' . $encryptModel->file->name . '.key');
            file_put_contents($keyFilename, $mediaKey);

            // Генерируем sidecar для видео/аудио
            $sidecarFilename = null;
            if (in_array($encryptModel->mediaType, ['VIDEO', 'AUDIO'])) {
                $sidecarGenerator = new SideCarGenerator([
                    'source' => $encryptModel->file->tempName,
                    'mediaKey' => $mediaKey,
                    'mediaType' => $encryptModel->mediaType
                ]);

                $sidecarFilename = Yii::getAlias('@runtime/' . uniqid() . '_' . $encryptModel->file->name . '.sidecar');
                $sidecarGenerator->saveToFile($sidecarFilename);
            }

            return [
                'success' => true,
                'mediaKey' => base64_encode($mediaKey),
                'encryptedFile' => basename($encryptedFilename),
                'keyFile' => basename($keyFilename),
                'sidecarFile' => $sidecarFilename ? basename($sidecarFilename) : null,
                'originalSize' => $encryptModel->file->size,
                'encryptedSize' => filesize($encryptedFilename)
            ];

        } catch (\Exception $e) {
            Yii::error('Encryption error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Дешифрование файла
     */
    public function actionDecrypt()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $decryptModel = new DecryptForm();
        $postData = Yii::$app->request->post();
        Yii::info('Decrypt POST data: ' . print_r($postData, true), 'whatsapp');
        $decryptModel->load($postData);
        $decryptModel->file = UploadedFile::getInstance($decryptModel, 'file');
        
        Yii::info('Decrypt model after load: mediaKey=' . ($decryptModel->mediaKey ?? 'null') . ', mediaType=' . ($decryptModel->mediaType ?? 'null'), 'whatsapp');

        if (!$decryptModel->validate()) {
            return ['error' => 'Ошибка валидации: ' . implode(', ', $decryptModel->getFirstErrors())];
        }

        try {
            $mediaKeyBase64 = $decryptModel->mediaKey;
            Yii::info('Decrypt: mediaKey base64=' . $mediaKeyBase64, 'whatsapp');
            
            $mediaKey = base64_decode($mediaKeyBase64, true);
            if ($mediaKey === false || strlen($mediaKey) !== 32) {
                Yii::error('Decrypt: Invalid mediaKey format. Decoded length=' . ($mediaKey === false ? 'false' : strlen($mediaKey)), 'whatsapp');
                return ['error' => 'Неверный формат media key. Ожидается 32 байта после декодирования base64'];
            }
            
            Yii::info('Decrypt: mediaKey decoded length=' . strlen($mediaKey) . ', hex=' . bin2hex($mediaKey), 'whatsapp');

            // Проверка и логирование файла перед дешифрованием
            if (!file_exists($decryptModel->file->tempName)) {
                return ['error' => 'Временный файл не найден: ' . $decryptModel->file->tempName];
            }
            
            $fileSize = filesize($decryptModel->file->tempName);
            $fileContent = file_get_contents($decryptModel->file->tempName);
            $readSize = strlen($fileContent);
            
            Yii::info('Decrypt file info: tempName=' . $decryptModel->file->tempName . ', fileSize=' . $fileSize . ', readSize=' . $readSize . ', originalSize=' . $decryptModel->file->size, 'whatsapp');
            Yii::info('Decrypt file first 32 bytes (hex): ' . bin2hex(substr($fileContent, 0, min(32, $readSize))), 'whatsapp');
            
            if ($readSize < 26) {
                return ['error' => 'Файл слишком короткий для дешифрования. Размер: ' . $readSize . ' байт (минимум 26 байт)'];
            }
            
            if ($readSize != $fileSize) {
                return ['error' => 'Файл прочитан не полностью. Размер файла: ' . $fileSize . ' байт, прочитано: ' . $readSize . ' байт'];
            }

            // Передаем содержимое файла напрямую, так как мы уже его прочитали
            $decryptionStream = new DecryptionStream([
                'source' => $fileContent,
                'mediaKey' => $mediaKey,
                'mediaType' => $decryptModel->mediaType
            ]);

            $decryptedFilename = Yii::getAlias('@runtime/' . uniqid() . '_' . $decryptModel->file->name . '.decrypted');
            $decryptionStream->saveToFile($decryptedFilename);

            return [
                'success' => true,
                'decryptedFile' => basename($decryptedFilename),
                'size' => filesize($decryptedFilename)
            ];

        } catch (\Exception $e) {
            Yii::error('Decryption error: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Пакетная обработка файлов из samples
     */
    public function actionProcessSamples()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $samplesPath = Yii::getAlias('@app/samples');
        $results = [];

        // Если папки samples нет, создаем тестовые файлы
        if (!file_exists($samplesPath)) {
            $this->createTestSamples($samplesPath);
        }

        foreach (['IMAGE', 'VIDEO', 'AUDIO', 'DOCUMENT'] as $mediaType) {
            $pattern = $samplesPath . '/*.' . strtolower($mediaType) . '.original';
            $files = glob($pattern);

            foreach ($files as $originalFile) {
                $baseName = basename($originalFile, '.original');
                $keyFile = $samplesPath . '/' . $baseName . '.key';
                $encryptedFile = $samplesPath . '/' . $baseName . '.encrypted';

                if (!file_exists($keyFile) || !file_exists($encryptedFile)) {
                    continue;
                }

                try {
                    $mediaKey = file_get_contents($keyFile);

                    // Дешифруем
                    $decryptionStream = new DecryptionStream([
                        'source' => $encryptedFile,
                        'mediaKey' => $mediaKey,
                        'mediaType' => $mediaType
                    ]);

                    $decryptedContent = $decryptionStream->getDecryptedData();
                    $originalContent = file_get_contents($originalFile);

                    $results[] = [
                        'file' => $baseName,
                        'mediaType' => $mediaType,
                        'success' => $decryptedContent === $originalContent,
                        'originalSize' => strlen($originalContent),
                        'decryptedSize' => strlen($decryptedContent),
                        'match' => $decryptedContent === $originalContent ? '✓' : '✗'
                    ];

                } catch (\Exception $e) {
                    $results[] = [
                        'file' => $baseName,
                        'mediaType' => $mediaType,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'match' => 'ERROR'
                    ];
                }
            }
        }

        if (empty($results)) {
            return ['message' => 'Тестовые файлы не найдены в папке samples. Созданы демо-файлы.'];
        }

        return $results;
    }

    /**
     * Создает тестовые файлы для демонстрации
     */
    private function createTestSamples(string $samplesPath)
    {
        if (!file_exists($samplesPath)) {
            mkdir($samplesPath, 0777, true);
        }

        $testFiles = [
            'test1.image.original' => 'Это тестовое изображение ' . str_repeat('X', 100),
            'test2.video.original' => 'Это тестовое видео ' . str_repeat('Y', 200),
            'test3.audio.original' => 'Это тестовый аудиофайл ' . str_repeat('Z', 150),
            'test4.document.original' => 'Это тестовый документ ' . str_repeat('A', 300),
        ];

        foreach ($testFiles as $filename => $content) {
            $originalFile = $samplesPath . '/' . $filename;
            file_put_contents($originalFile, $content);

            $baseName = basename($filename, '.original');
            $mediaType = strtoupper(explode('.', $filename)[1]);

            // Генерируем ключ
            $mediaKey = Yii::$app->security->generateRandomKey(32);
            $keyFile = $samplesPath . '/' . $baseName . '.key';
            file_put_contents($keyFile, $mediaKey);

            // Шифруем файл
            $encryptionStream = new EncryptionStream([
                'source' => $originalFile,
                'mediaKey' => $mediaKey,
                'mediaType' => $mediaType
            ]);

            $encryptedFile = $samplesPath . '/' . $baseName . '.encrypted';
            $encryptionStream->saveToFile($encryptedFile);

            // Генерируем sidecar для видео/аудио
            if (in_array($mediaType, ['VIDEO', 'AUDIO'])) {
                $sidecarGenerator = new SideCarGenerator([
                    'source' => $originalFile,
                    'mediaKey' => $mediaKey,
                    'mediaType' => $mediaType
                ]);

                $sidecarFile = $samplesPath . '/' . $baseName . '.sidecar';
                $sidecarGenerator->saveToFile($sidecarFile);
            }
        }
    }

    /**
     * Скачивание файла из runtime
     */
    public function actionDownload($file)
    {
        $filePath = Yii::getAlias('@runtime/' . $file);

        if (!file_exists($filePath) || !is_file($filePath)) {
            throw new \yii\web\NotFoundHttpException('Файл не найден');
        }

        return Yii::$app->response->sendFile($filePath);
    }



    /**
     * Очистка папки runtime от созданных файлов
     */
    public function actionClearRuntime()
    {
        $runtimePath = Yii::getAlias('@runtime');
        $files = glob($runtimePath . '/*.{encrypted,key,sidecar,decrypted}', GLOB_BRACE);

        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deleted++;
            }
        }

        Yii::$app->session->setFlash('success', "Удалено {$deleted} файлов из runtime");
        return $this->redirect(['index']);
    }
}