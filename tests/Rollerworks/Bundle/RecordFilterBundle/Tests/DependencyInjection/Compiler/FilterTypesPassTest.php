<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;

use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\Compiler\AddFilterTypesPass;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;

class FilterTypesPassTest extends TestCase
{
    public function testTaggedModifier()
    {
        $dateType = new Definition('DateTypeClass');
        $dateType->addTag('rollerworks_record_filter.filter_type', array('alias' => 'date'));

        $numberTypeClass = new Definition('NumberTypeClass');
        $numberTypeClass->addTag('rollerworks_record_filter.filter_type', array('alias' => 'number'));

        $decimalTypeClass = new Definition('NumberTypeClass');
        $decimalTypeClass->addTag('rollerworks_record_filter.filter_type');

        $typeFactory = new Definition('Rollerworks\Bundle\RecordFilterBundle\Factory\FilterTypeFactory');
        $typeFactory->setArguments(array('container', array()));

        $container = $this->createContainer();
        $container->setDefinition('rollerworks_record_filter.types_factory', $typeFactory);

        $container->setDefinition('date_type_service', $dateType);
        $container->setDefinition('number_type_service', $numberTypeClass);
        $container->setDefinition('decimal_type_service', $decimalTypeClass);

        $pass = new AddFilterTypesPass();
        $pass->process($container);

        $this->assertEquals('prototype', $container->getDefinition('date_type_service')->getScope());
        $this->assertEquals('prototype', $container->getDefinition('number_type_service')->getScope());

        // This service was not fully tagged and must be ignored.
        $this->assertFalse($container->getDefinition('decimal_type_service')->isAbstract());

        $arguments = $container->getDefinition('rollerworks_record_filter.types_factory')->getArgument(1);
        $this->assertEquals(array(
            'date' => 'date_type_service',
            'number' => 'number_type_service',
        ), $arguments);
    }
}
