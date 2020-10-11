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

use Rollerworks\Component\Search\Extension\Core\Type\TimestampType;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

/**
 * @internal
 */
final class TimestampTypeTest extends SearchIntegrationTestCase
{
    /** @test */
    public function transform_with_different_timezones(): void
    {
        $field = $this->getFactory()->createField('datetime', TimestampType::class, [
            'model_timezone' => 'Asia/Hong_Kong',
            'view_timezone' => 'America/New_York',
        ]);

        $output = new \DateTimeImmutable('2010-02-03 04:05:06 America/New_York');
        $input = $output->format('U');

        $output = $output->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));

        FieldTransformationAssertion::assertThat($field)
            ->withInput($input)
            ->successfullyTransformsTo($output)
            ->andReverseTransformsTo($input);
    }

    /** @test */
    public function invalid_input_should_fail_transformation(): void
    {
        $field = $this->getFactory()->createField('datetime', TimestampType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06*2010*02', '06*2010*02')
            ->failsToTransforms();
    }
}
