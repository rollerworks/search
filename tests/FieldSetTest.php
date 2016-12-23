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
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;

final class FieldSetTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_a_field()
    {
        $fieldSet = new FieldSet([
            'id' => $idField = $this->createFieldMock('id'),
            'name' => $nameField = $this->createFieldMock('name'),
        ]);

        self::assertSame($idField, $fieldSet->get('id'));
        self::assertSame($nameField, $fieldSet->get('name'));

        self::assertTrue($fieldSet->has('id'));
        self::assertTrue($fieldSet->has('name'));
        self::assertFalse($fieldSet->has('foo'));
    }

    /**
     * @param string $name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createFieldMock(string $name)
    {
        $field = $this->createMock(FieldConfigInterface::class);
        $field->expects(self::any())->method('getName')->willReturn($name);

        return $field;
    }
}
