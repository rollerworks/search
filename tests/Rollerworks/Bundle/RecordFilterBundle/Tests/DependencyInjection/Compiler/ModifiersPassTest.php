<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\Compiler\FormatterModifiersPass;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;

class ModifiersPassTest extends TestCase
{
    public function testTaggedModifier()
    {
        $validationModifier = new Definition('ValidatorClass');
        $validationModifier->addTag('rollerworks_record_filter.formatter_modifier');

        $valueOptimizerModifier = new Definition('ValueOptimizerClass');
        $valueOptimizerModifier->addTag('rollerworks_record_filter.formatter_modifier');

        $formatter = new Definition('Rollerworks\Bundle\RecordFilterBundle\Formatter\Formatter');

        $container = $this->createContainer();
        $container->setDefinition('rollerworks_record_filter.modifier_formatter', $formatter);

        $container->setDefinition('validation_modifier_service', $validationModifier);
        $container->setDefinition('value_optimizer_modifier_service', $valueOptimizerModifier);

        $profilerPass = new FormatterModifiersPass();
        $profilerPass->process($container);

        $methodCalls = $container->getDefinition('rollerworks_record_filter.modifier_formatter')->getMethodCalls();

        $this->assertEquals(2, count($methodCalls));

        $this->assertEquals('registerModifier', $methodCalls[0][0]);
        $this->assertEquals('registerModifier', $methodCalls[1][0]);

        $this->assertEquals(array(new Reference('validation_modifier_service')), $methodCalls[0][1]);
        $this->assertEquals(array(new Reference('value_optimizer_modifier_service')), $methodCalls[1][1]);
    }

    public function testTaggedModifierSorting()
    {
        $validationModifier = new Definition('ValidatorClass');
        $validationModifier->addTag('rollerworks_record_filter.formatter_modifier', array('priority' => 1));

        $valueOptimizerModifier = new Definition('ValueOptimizerClass');
        $valueOptimizerModifier->addTag('rollerworks_record_filter.formatter_modifier', array('priority' => 0));

        $formatter = new Definition('Rollerworks\Bundle\RecordFilterBundle\Formatter\Formatter');

        $container = $this->createContainer();
        $container->setDefinition('rollerworks_record_filter.modifier_formatter', $formatter);

        $container->setDefinition('validation_modifier_service',        $validationModifier);
        $container->setDefinition('value_optimizer_modifier_service',   $valueOptimizerModifier);

        $profilerPass = new FormatterModifiersPass();
        $profilerPass->process($container);

        $methodCalls = $container->getDefinition('rollerworks_record_filter.modifier_formatter')->getMethodCalls();

        $this->assertEquals(2, count($methodCalls));

        $this->assertEquals('registerModifier', $methodCalls[0][0]);
        $this->assertEquals('registerModifier', $methodCalls[1][0]);

        $this->assertEquals(array(new Reference('validation_modifier_service')),         $methodCalls[0][1]);
        $this->assertEquals(array(new Reference('value_optimizer_modifier_service')),    $methodCalls[1][1]);
    }
}
