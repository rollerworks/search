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

namespace Rollerworks\Component\Search\Tests;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\GenericSearchFactory;
use Rollerworks\Component\Search\SearchFactoryBuilder;
use Rollerworks\Component\Search\Tests\Fixtures\FooType;

/**
 * @internal
 */
final class SearchFactoryBuilderTest extends TestCase
{
    /** @var \ReflectionProperty */
    private $registry;
    private $type;

    protected function setUp()
    {
        $factory = new \ReflectionClass(GenericSearchFactory::class);
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

        self::assertCount(1, $extensions);
        self::assertTrue($extensions[0]->hasType(FooType::class));
    }
}
