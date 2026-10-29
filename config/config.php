<?php
declare(strict_types=1);

$config = [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'swd_students',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'ssl_ca' => getenv('DB_SSL_CA') ?: null,
    ],
    'app' => [
        'read_only' => filter_var(getenv('APP_READ_ONLY') ?: '0', FILTER_VALIDATE_BOOLEAN),
    ],
];

$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $localConfig = require $localConfigPath;
    if (!is_array($localConfig)) {
        throw new RuntimeException('Local configuration must return an array.');
    }

    foreach (['db', 'app'] as $section) {
        if (isset($localConfig[$section])) {
            if (!is_array($localConfig[$section])) {
                throw new RuntimeException('Local configuration section "' . $section . '" must be an array.');
            }
            $config[$section] = array_replace($config[$section], $localConfig[$section]);
        }
    }
}

return $config;
