<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Types;

use Rollerworks\RecordFilterBundle\Type\DateTime;

abstract class DateTimeTestCase extends \Rollerworks\RecordFilterBundle\Tests\TestCase
{
    protected function assertDateTimeEqual($expected, $input, $withTime = false)
    {
        $this->assertInstanceOf('\DateTime', $input);

        /** @var \DateTime $input */
        $this->assertEquals($expected, $input->format('Y-m-d' . ($withTime ? ' H:i' : '' )));
    }

    protected function assertDateTimeNotEqual($expected, $input, $withTime = false)
    {
        $this->assertInstanceOf('\DateTime', $input);

        /** @var \DateTime $input */
        $this->assertNotEquals($expected, $input->format('Y-m-d' . ($withTime ? ' H:i' : '' )));
    }
}
