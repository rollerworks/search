<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class FieldTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('name', 'field');
    }

    public function testMappingsAllowed()
    {
        $this->getFactory()->createField('name', 'field', ['model_mappings' => [['stdClass', 'id']]]);
    }

    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Option "model_mappings" cannot be set in combination with "model_class"
     */
    public function testMappingsCannotBeCombinedWithModelClass()
    {
        $this->getFactory()->createField('name', 'field', ['model_mappings' => [['stdClass', 'id']], 'model_class' => 'std']);
    }

    /**
     * @expectedException \Rollerworks\Component\Search\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Option "model_mappings" cannot be set in combination with "model_class"
     */
    public function testMappingsCannotBeCombinedWithModelProperty()
    {
        $this->getFactory()->createField('name', 'field', ['model_mappings' => [['stdClass', 'id']], 'model_property' => 'std']);
    }

    protected function getTestedType()
    {
        return 'field';
    }
}
