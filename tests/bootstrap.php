<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

error_reporting(E_ALL | E_STRICT);

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    throw new \RuntimeException('Did not find vendor/autoload.php. Did you run "composer install"?');
}

if (version_compare(PHP_VERSION, '5.4', '>=') && gc_enabled()) {
    // Disabling Zend Garbage Collection to prevent segfaults with PHP5.4+
    // https://bugs.php.net/bug.php?id=53976
    gc_disable();
}

date_default_timezone_set('UTC');

/**
 * @var \Composer\Autoload\ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
