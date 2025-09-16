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

use Rollerworks\Component\Search\Extension\Core\Type\BooleanType;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

/**
 * @internal
 */
final class BooleanTypeTest extends SearchIntegrationTestCase
{
    /** @test */
    public function transform_to_boolean(): void
    {
        $field = $this->getFactory()->createField('active', BooleanType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput(null)
            ->successfullyTransformsTo(null)
            ->andReverseTransformsTo('')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('true')
            ->successfullyTransformsTo(true)
            ->andReverseTransformsTo('yes', 'true')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1', 'true')
            ->successfullyTransformsTo(true)
            ->andReverseTransformsTo('yes', 'true')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('false')
            ->successfullyTransformsTo(false)
            ->andReverseTransformsTo('no', 'false')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('false', false)
            ->successfullyTransformsTo(false)
            ->andReverseTransformsTo('no', 'false')
        ;
    }

    /** @test */
    public function transform_to_boolean_with_custom_label_and_norm(): void
    {
        $field = $this->getFactory()->createField('active', BooleanType::class, [
            'view_label' => ['true' => 'ja', 'false' => 'nee'],
            'norm_label' => ['true' => 'yes', 'false' => 'no'],
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput(null)
            ->successfullyTransformsTo(null)
            ->andReverseTransformsTo('')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('ja', 'yes')
            ->successfullyTransformsTo(true)
            ->andReverseTransformsTo('ja', 'yes')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('nee', 'no')
            ->successfullyTransformsTo(false)
            ->andReverseTransformsTo('nee', 'no')
        ;
    }

    /** @test */
    public function wrong_input_fails(): void
    {
        $field = $this->getFactory()->createField('active', BooleanType::class);

        FieldTransformationAssertion::assertThat($field)->withInput('foo')->failsToTransforms();
    }
}
