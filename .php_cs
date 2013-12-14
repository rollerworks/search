<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude(array('bin', 'docs'))
    ->in(__DIR__ . '/src')
;

return Symfony\CS\Config\Config::create()
    ->finder($finder)
;
