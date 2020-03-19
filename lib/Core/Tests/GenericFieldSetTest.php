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
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\SearchFieldView;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\GenericFieldSet;

/**
 * @internal
 */
final class GenericFieldSetTest extends TestCase
{
    /**
     * @test
     */
    public function it_gets_a_field()
    {
        $fieldSet = new GenericFieldSet([
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
     * @test
     */
    public function it_creates_a_view()
    {
        $fieldSet = new GenericFieldSet([
            'id' => $idField = $this->createFieldMock('id', true),
            'name' => $nameField = $this->createFieldMock('name', true),
        ]);

        $view = $fieldSet->createView();
        $expectedView = new FieldSetView();

        $idView = new SearchFieldView($expectedView);
        $idView->vars['name'] = 'id';
        $expectedView->fields['id'] = $idView;

        $nameView = new SearchFieldView($expectedView);
        $nameView->vars['name'] = 'name';
        $expectedView->fields['name'] = $nameView;

        self::assertEquals($expectedView, $view);
    }

    /**
     * @test
     */
    public function it_creates_a_view_with_builder()
    {
        $fieldSet = new GenericFieldSet(
            [
                'id' => $idField = $this->createFieldMock('id', true),
                'name' => $nameField = $this->createFieldMock('name', true),
            ],
            'test',
            function (FieldSetView $view) {
                $view->vars['name'] = 'something';
            }
        );

        $view = $fieldSet->createView();
        $expectedView = new FieldSetView();
        $expectedView->vars['name'] = 'something';

        $idView = new SearchFieldView($expectedView);
        $idView->vars['name'] = 'id';
        $expectedView->fields['id'] = $idView;

        $nameView = new SearchFieldView($expectedView);
        $nameView->vars['name'] = 'name';
        $expectedView->fields['name'] = $nameView;

        self::assertEquals($expectedView, $view);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createFieldMock(string $name, bool $withView = false)
    {
        $field = $this->createMock(FieldConfig::class);
        $field->expects(self::any())->method('getName')->willReturn($name);

        if ($withView) {
            $field->expects(self::once())
                ->method('createView')
                ->willReturnCallback(function (FieldSetView $view) use ($name) {
                    $fieldView = new SearchFieldView($view);
                    $fieldView->vars['name'] = $name;

                    return $fieldView;
                });
        }

        return $field;
    }
}
