<?php

declare(strict_types=1);

$candidates = [
    dirname(__DIR__, 3) . '/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

$autoload = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "Unable to locate Composer autoload.php for wonderwp/mailing unit tests.\n");
    exit(1);
}

$functionsFile = dirname(__DIR__) . '/vendor/wonderwp/functions/src/functions.php';
if (is_file($functionsFile)) {
    require_once $functionsFile;
} else {
    require_once __DIR__ . '/stub-functions.php';
}

require_once __DIR__ . '/stub-mandrill.php';
require_once $autoload;

spl_autoload_register(static function (string $class): void {
    $prefix = 'WonderWp\\Component\\Mailing\\Tests\\';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
