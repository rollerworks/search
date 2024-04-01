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

namespace Rollerworks\Component\Search\Test;

use Carbon\CarbonInterval;
use SebastianBergmann\Comparator\Comparator;
use SebastianBergmann\Comparator\ComparisonFailure;
use SebastianBergmann\Exporter\Exporter;

final class CarbonIntervalComparator extends Comparator
{
    public function accepts(mixed $expected, mixed $actual): bool
    {
        return $expected instanceof CarbonInterval && $actual instanceof CarbonInterval;
    }

    /**
     * @param CarbonInterval $expected
     * @param CarbonInterval $actual
     */
    public function assertEquals($expected, $actual, $delta = 0.0, $canonicalize = false, $ignoreCase = false): void
    {
        if ($expected->invert === $actual->invert && $expected->forHumans() === $actual->forHumans()) {
            return;
        }

        $exporter = new Exporter();

        throw new ComparisonFailure(
            $expected,
            $actual,
            $exportedExpected = $exporter->export($expected),
            $exportedActual = $exporter->export($actual),
            false,
            sprintf(
                'Failed asserting that %s matches expected %s.',
                $exportedActual,
                $exportedExpected
            )
        );
    }
}
