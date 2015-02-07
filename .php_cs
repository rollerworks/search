<?php

return Symfony\CS\Config\Config::create()
    ->setUsingLinter(false)
    // use SYMFONY_LEVEL:
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    // and extra fixers:
    ->fixers(array(
        'ordered_use',
        //'strict',
        'strict_param',
        '-prs0'
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->exclude(array('bin', 'doc'))
            ->in(__DIR__)
    )
;
