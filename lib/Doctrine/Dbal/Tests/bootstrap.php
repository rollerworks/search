<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

\error_reporting(\E_ALL | \E_STRICT);

if (! \file_exists(__DIR__ . '/../vendor/autoload.php')) {
    throw new \RuntimeException('Did not find vendor/autoload.php. Did you run "composer install --prefer-source --dev"?');
}

\date_default_timezone_set('UTC');

require __DIR__ . '/../vendor/autoload.php';
