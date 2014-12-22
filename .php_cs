<?php

return Symfony\CS\Config\Config::create()
    // use SYMFONY_LEVEL:
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    // and extra fixers:
    ->fixers(array(
        'ordered_use',
        //'strict',
        'strict_param',
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude(array('bin', 'doc', 'spec'))
            ->in(__DIR__)
    )
;
