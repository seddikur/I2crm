<?php
// Указываем полный путь относительно текущего файла (web/test.php)
// samples/ находится на уровень выше web/, поэтому используем ../samples/
//$keyPath = __DIR__ . '/i2crm-php-test-task-a0d44ecafc60/samples/AUDIO.key';
$keyPath = __DIR__ . '/i2crm-php-test-task-a0d44ecafc60/samples/IMAGE.key';

if (!file_exists($keyPath)) {
    die("Файл не найден: " . htmlspecialchars($keyPath));
}

$binaryKey = file_get_contents($keyPath);

if ($binaryKey === false) {
    die("Не удалось прочитать файл.");
}

if (strlen($binaryKey) !== 32) {
    echo "Внимание: длина ключа не 32 байта (фактически: " . strlen($binaryKey) . ")\n";
}

echo base64_encode($binaryKey);