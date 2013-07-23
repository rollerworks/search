<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

error_reporting(E_ALL | E_STRICT);


if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new \RuntimeException('Did not find vendor/autoload.php. Please Install vendors using command: composer.phar install --dev');
}

if (version_compare(PHP_VERSION, '5.4', '>=') && gc_enabled()) {
    // Disabling Zend Garbage Collection to prevent segfaults with PHP5.4+
    // https://bugs.php.net/bug.php?id=53976
    gc_disable();
}

/**
* @var $loader ClassLoader
*/
$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->add('Rollerworks\\Bundle\\RecordFilterBundle\\Tests', __DIR__);
$loader->add('Doctrine\\Tests', realpath(__DIR__ . '/../vendor/doctrine/orm/tests') . '/');

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

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
