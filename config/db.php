<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=yii2_db;dbname=yii_basa;charset=utf8mb4',
    'username' => 'user',
    'password' => 'secret',
    'charset' => 'utf8mb4',
    'enableSchemaCache' => false,


    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
