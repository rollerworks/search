<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests;

use Rollerworks\Component\Search\SearchFactoryBuilder;
use Rollerworks\Component\Search\Tests\Fixtures\FooType;

class SearchFactoryBuilderTest extends \PHPUnit_Framework_TestCase
{
    private $registry;
    private $type;

    protected function setUp()
    {
        $factory = new \ReflectionClass('Rollerworks\Component\Search\SearchFactory');
        $this->registry = $factory->getProperty('registry');
        $this->registry->setAccessible(true);

        $this->type = new FooType();
    }

    public function testAddType()
    {
        $factoryBuilder = new SearchFactoryBuilder();
        $factoryBuilder->addType($this->type);

        $factory = $factoryBuilder->getSearchFactory();
        $registry = $this->registry->getValue($factory);
        $extensions = $registry->getExtensions();

        $this->assertCount(1, $extensions);
        $this->assertTrue($extensions[0]->hasType($this->type->getName()));
    }
}
