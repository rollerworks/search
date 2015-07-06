<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Doctrine\Common\Annotations\AnnotationRegistry;

error_reporting(E_ALL | E_STRICT);

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    throw new \RuntimeException('Did not find vendor/autoload.php. Did you run "composer install"?');
}

/*
 * @var \Composer\Autoload\ClassLoader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

// Don't use the system tempdir on Windows as that fails to work!
// The path gets to long when it also includes the 6 character hash of the Container with functional tests.
call_user_func(
    function () {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $rootDir = substr(realpath(substr(__DIR__, 0, strpos(__DIR__, '\\') + 1)), 0, -1);
        } else {
            $rootDir = sys_get_temp_dir();
        }

        $rootDir .= '/.tmp_c';

        putenv('TMPDIR='.$rootDir);

        if (!is_dir($rootDir)) {
            mkdir($rootDir, 0777, true);
        }
    }
);
