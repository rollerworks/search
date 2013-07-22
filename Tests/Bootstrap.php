<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

call_user_func(function() {
    if (!is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
        throw new \RuntimeException('Did not find vendor/autoload.php. Did you run "composer install --dev"?');
    }

    /** @var \Composer\Autoload\ClassLoader $loader */
    $loader = require_once __DIR__ . '/../vendor/autoload.php';
    $loader->add('Doctrine\\Tests', str_replace('\\', '/', realpath(__DIR__ . '/../vendor/doctrine/orm/tests')) . '/');

    $bundleLoader = function($v) {
        if (0 !== strpos($v, 'Rollerworks\\Bundle\\RecordFilterBundle')) {
            return false;
        }

        if (!is_file($file = __DIR__.'/../'.str_replace('\\', '/', substr($v, 38)).'.php')) {
            return false;
        }

        require_once $file;

        return true;
    };
    spl_autoload_register($bundleLoader);

    AnnotationRegistry::registerLoader($bundleLoader);
    AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
});

// Don't use the system tempdir on Windows as that fails to work!
call_user_func(function () {
    if ('' == getenv('TMPDIR')) {
        if (false !== $pos = strpos(__DIR__, '\\')) {
            $rootDir = substr(realpath(substr(__DIR__, 0, $pos + 1)), 0, -1);
        } else {
            $rootDir = sys_get_temp_dir();
        }

        $rootDir .= '/.tmp_c';
        putenv('TMPDIR=' . $rootDir);

        if (!is_dir($rootDir)) {
            mkdir($rootDir, 0777, true);
        }
    }
});
