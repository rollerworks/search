<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\Extension\Core\CoreExtension;
use Rollerworks\Component\Search\FieldRegistry;
use Rollerworks\Component\Search\ResolvedFieldTypeFactory;
use Rollerworks\Component\Search\SearchFactory;

class SearchIntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
    * @var SearchFactory
    */
    protected $factory;

    protected function setUp()
    {
        $resolvedTypeFactory = new ResolvedFieldTypeFactory();

        $extensions = array(new CoreExtension());
        $extensions = array_merge($extensions, $this->getExtensions());

        $typesRegistry = new FieldRegistry($extensions, $resolvedTypeFactory);
        $this->factory = new SearchFactory($typesRegistry, $resolvedTypeFactory, null);
    }

    protected function getExtensions()
    {
        return array();
    }
}
