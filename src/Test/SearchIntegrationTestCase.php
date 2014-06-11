<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
