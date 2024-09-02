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

namespace Rollerworks\Component\Search\Tests\Field;

use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\OrderTransformer;
use Rollerworks\Component\Search\Field\OrderField;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

/**
 * @internal
 */
final class OrderFieldTypeTest extends SearchIntegrationTestCase
{
    /** @test */
    public function it_transforms_with_default_configuration(): void
    {
        /** @var OrderField $field */
        $field = $this->getFactory()->createField('@id', OrderFieldType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('DESC')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('desc', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('desc', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('ASC')
            ->successfullyTransformsTo('ASC')
            ->andReverseTransformsTo('asc', 'ASC')
        ;
    }

    /** @test */
    public function it_transforms_with_alias(): void
    {
        /** @var OrderField $field */
        $field = $this->getFactory()->createField('@id', OrderFieldType::class, ['alias' => [
            'UP' => 'ASC',
            'DOWN' => 'DESC',

            'OMHOOR' => 'ASC',
            'NEER' => 'DESC',
        ]]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('down')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('desc', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('NEER')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('desc', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('desc', 'DESC')
        ;
    }

    /** @test */
    public function it_transforms_with_view_label(): void
    {
        /** @var OrderField $field */
        $field = $this->getFactory()->createField('@id', OrderFieldType::class, ['view_label' => [
            'ASC' => 'up',
            'DESC' => 'down',
        ]]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('down')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Up')
            ->successfullyTransformsTo('ASC')
            ->andReverseTransformsTo('up', 'ASC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;
    }

    /** @test */
    public function it_transforms_with_alias_and_view_label(): void
    {
        /** @var OrderField $field */
        $field = $this->getFactory()->createField('@id', OrderFieldType::class, [
            'view_label' => [
                'ASC' => 'up',
                'DESC' => 'down',
            ],
            'alias' => [
                'UP' => 'ASC',
                'DOWN' => 'DESC',
                'OMHOOR' => 'ASC',
                'NEER' => 'DESC',
            ],
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('down')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Up')
            ->successfullyTransformsTo('ASC')
            ->andReverseTransformsTo('up', 'ASC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('down')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('NEER')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;
    }

    /** @test */
    public function it_transforms_with_alias_and_view_label_and_lowercase(): void
    {
        /** @var OrderField $field */
        $field = $this->getFactory()->createField('@id', OrderFieldType::class, [
            'view_label' => [
                'asc' => 'up',
                'desc' => 'down',
            ],
            'alias' => [
                'up' => 'ASC',
                'down' => 'DESC',
                'omhoor' => 'ASC',
                'neer' => 'DESC',
            ],
            'case' => OrderTransformer::CASE_LOWERCASE,
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('down')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Up')
            ->successfullyTransformsTo('ASC')
            ->andReverseTransformsTo('up', 'ASC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('down')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC')
        ;

        FieldTransformationAssertion::assertThat($field)
            ->withInput('NEER')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('desc')
            ->successfullyTransformsTo('DESC')
            ->andReverseTransformsTo('down', 'DESC');
    }

    /** @test */
    public function it_fails_to_transform_with_invalid_direction(): void
    {
        /** @var OrderField $field */
        $field = $this->getFactory()->createField('@id', OrderFieldType::class, [
            'view_label' => [
                'asc' => 'up',
                'desc' => 'down',
            ],
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('neer')
            ->failsToTransforms(
                new TransformationFailedException(
                    'Invalid sort direction "NEER" specified, expected one of: "ASC", "DESC", "UP", "DOWN"',
                    0,
                    null,
                    'This value is not a valid sorting direction. Accepted directions are: {{ directions }}.',
                    ['{{ directions }}' => ['asc', 'desc', 'up', 'down']]
                )
            )
        ;
    }
}
