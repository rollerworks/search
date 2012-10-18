<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude('vendor')
    ->exclude('.temp') // this directory is only used local.
    ->exclude('Tests/Fixtures/Views')
    ->exclude('Tests/.cache')
    ->exclude('.idea')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->finder($finder)
;
