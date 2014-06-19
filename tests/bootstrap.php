<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;

error_reporting(E_ALL | E_STRICT);

if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new \RuntimeException('Did not find vendor/autoload.php. Please Install vendors using command: composer.phar install');
}

if (version_compare(PHP_VERSION, '5.4', '>=') && gc_enabled()) {
    // Disabling Zend Garbage Collection to prevent segfaults with PHP5.4+
    // https://bugs.php.net/bug.php?id=53976
    gc_disable();
}

/**
* @var \Composer\Autoload\ClassLoader $loader
*/
$loader = require_once __DIR__ . '/../vendor/autoload.php';
$loader->add('Rollerworks\\Component\\Search\\Tests\\', __DIR__);

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
