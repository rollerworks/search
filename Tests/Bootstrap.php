<?php

require_once __DIR__ . '/../../../../../../app/bootstrap.php.cache';

$loader->registerNamespaces(array(
    'Rollerworks'      => array(__DIR__.'/../../../../..', __DIR__.'/../../../../../bundles'),

    // When using Composer this will properly fail. Try to make it more robust
    'Doctrine\Tests'   => __DIR__.'/../../../../../doctrine/tests'
));

//AnnotationRegistry::registerFile(__DIR__.'/../Filter/FilterAnnotations.php');
