<?php

set_time_limit(0);
ini_set('memory_limit', -1);

$autoload = __DIR__ . '/../vendor/autoload.php';
$options = getopt(null, ['path:', 'docgenerator']);
$path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/';
$base = __DIR__ . '/../';
$configFile = $base . '.config.json';
$config = [];

if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);
}

if (isset($options['path'])) {
    $config['path'] = rtrim($options['path'], '/') . '/';
}

spl_autoload_register(function ($class) {
    $prefix = 'SocialEngine\\Console\\';
    $base = __DIR__ . '/../src/';
    $len = strlen($prefix);

    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $base . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

try {
    if (!file_exists($autoload)) {
        $autoload = $path . 'application/vendor/autoload.php';
    }

    if (!file_exists($autoload)) {
        throw new Exception('Run composer install first.');
    }

    require($autoload);

    $console = new SocialEngine\Console\Console($config);
    if (isset($options['docgenerator'])) {
        new SocialEngine\Console\Helper\DocGenerator($console);
    }
    $console->run();
} catch (Exception $e) {
    fwrite(STDOUT, $e->getMessage() . PHP_EOL);
    exit(1);
}
