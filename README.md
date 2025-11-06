# Порядок установки проекта

* Запуск Docker `` docker compose up -d ``
* Запуск зависимостей `` docker-compose run --rm composer install --ignore-platform-reqs ``- это только с этим docker-compose.yml
* Переход в контейнер  `` docker-compose exec -it web bash ``
* Запуск установки расширений yii2 `` composer install ``
* Очистка кеш `` composer clear-cache ``
* Запуск обновлений расширений yii2 `` composer update ``
* Запуск миграций `` php yii migrate `` 

# Шифрование файла
``
$encryptionStream = new \app\components\whatsapp\EncryptionStream([
'source' => '/path/to/file.jpg',
'mediaKey' => $mediaKey,
'mediaType' => 'IMAGE'
]);

$encryptedData = $encryptionStream->getEncryptedData();
``


# Дешифрование файла  
``
$decryptionStream = new \app\components\whatsapp\DecryptionStream([
'source' => '/path/to/file.jpg.encrypted',
'mediaKey' => $mediaKey,
'mediaType' => 'IMAGE'
]);

$decryptedData = $decryptionStream->getDecryptedData();
``


# Генерация sidecar
``
$sidecarGenerator = new \app\components\whatsapp\SideCarGenerator([
'source' => '/path/to/video.mp4',
'mediaKey' => $mediaKey,
'mediaType' => 'VIDEO'
]);

$sidecarData = $sidecarGenerator->generate();
``


