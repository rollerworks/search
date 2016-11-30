<?php

require_once __DIR__.'/vendor/sllh/php-cs-fixer-styleci-bridge/autoload.php';

use SLLH\StyleCIBridge\ConfigBridge;
use Symfony\CS\Fixer\Contrib\HeaderCommentFixer;

$header = <<<EOF
This file is part of the RollerworksSearch package.

(c) Sebastiaan Stok <s.stok@rollerscapes.net>

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

// PHP-CS-Fixer 1.x
if (class_exists('Symfony\CS\Fixer\Contrib\HeaderCommentFixer')) {
    \Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);
}

$config = ConfigBridge::create()
    ->setUsingCache(true)
;

// PHP-CS-Fixer 2.x
if (method_exists($config, 'setRules')) {
    $config->setRules(
        array_merge(
            $config->getRules(),
            [
                'header_comment' => ['header' => $header],
            ]
        )
    );
}

return $config;
