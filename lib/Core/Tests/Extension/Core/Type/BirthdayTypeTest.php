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

use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

/**
 * @internal
 */
final class BirthdayTypeTest extends SearchIntegrationTestCase
{
    public function testDateOnlyInput()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'pattern' => 'yyyy-MM-dd',
            'allow_age' => false,
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('2010-06-02', '2010-06-02')
            ->successfullyTransformsTo(new \DateTime('2010-06-02'))
            ->andReverseTransformsTo('2010-06-02', '2010-06-02');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('21')
            ->failsToTransforms();
    }

    public function testAllowAgeInput()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'pattern' => 'yyyy-MM-dd',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('2010-06-02', '2010-06-02')
            ->successfullyTransformsTo(new \DateTime('2010-06-02'))
            ->andReverseTransformsTo('2010-06-02', '2010-06-02');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('15')
            ->successfullyTransformsTo(15)
            ->andReverseTransformsTo('15');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'allow_age' => true,
        ]);

        FieldTransformationAssertion::assertThat($field)->withInput('twenty')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('-21')->failsToTransforms();
        FieldTransformationAssertion::assertThat($field)->withInput('+21')->failsToTransforms();
    }

    public function testAgeInTheFutureFails()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'pattern' => 'yyyy-MM-dd',
        ]);

        $currentDate = new \DateTime('now + 1 day', new \DateTimeZone('UTC'));

        FieldTransformationAssertion::assertThat($field)->withInput($currentDate->format('Y-m-d'))->failsToTransforms();
    }

    public function testAgeInFutureWorksWhenAllowed()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'pattern' => 'yyyy-MM-dd',
            'allow_future_date' => true,
        ]);

        $currentDate = new \DateTime('now + 1 day', new \DateTimeZone('UTC'));
        $currentDate->setTime(0, 0, 0);

        FieldTransformationAssertion::assertThat($field)
            ->withInput($currentDate->format('Y-m-d'))
            ->successfullyTransformsTo($currentDate)
            ->andReverseTransformsTo($currentDate->format('Y-m-d'));
    }
}
