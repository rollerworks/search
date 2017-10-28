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

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

/**
 * @internal
 */
final class TextTypeTest extends SearchIntegrationTestCase
{
    public function testAcceptsAnyScalarInput()
    {
        $field = $this->getFactory()->createField('name', TextType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('foobar')
            ->successfullyTransformsTo('foobar')
            ->andReverseTransformsTo('foobar');
    }
}
