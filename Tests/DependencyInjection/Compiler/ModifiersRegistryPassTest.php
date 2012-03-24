<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

use Rollerworks\RecordFilterBundle\DependencyInjection\Compiler\FormatterModifiersRegistryPass;
use Rollerworks\RecordFilterBundle\Tests\TestCase;

class ModifiersRegistryPassTest extends TestCase
{
    public function testTaggedModifier()
    {
        $validationModifier = new Definition('ValidatorClass');
        $validationModifier->addTag('rollerworks_record_filter.formatter_post_modifier');

        $valueOptimizerModifier = new Definition('ValueOptimizerClass');
        $valueOptimizerModifier->addTag('rollerworks_record_filter.formatter_post_modifier');

        $preModifier = new Definition('PreModifierClass');
        $preModifier->addTag('rollerworks_record_filter.formatter_pre_modifier');

        $preModifier2 = new Definition('PreModifier2Class');
        $preModifier2->addTag('rollerworks_record_filter.formatter_pre_modifier');

        $modifiersRegistry = new Definition('Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry');

        $container = $this->createContainer();
        $container->setDefinition('validation_modifier_service',        $validationModifier);
        $container->setDefinition('value_optimizer_modifier_service',   $valueOptimizerModifier);

        $container->setDefinition('pre_modifier_service',   $preModifier);
        $container->setDefinition('pre2_modifier_service',  $preModifier2);

        $container->setDefinition('rollerworks_record_filter.formatter_factory.modifiers_registry', $modifiersRegistry);

        $profilerPass = new FormatterModifiersRegistryPass();
        $profilerPass->process($container);

        $methodCalls = $container->getDefinition('rollerworks_record_filter.formatter_factory.modifiers_registry')->getMethodCalls();

        $this->assertEquals(4, count($methodCalls));

        $this->assertEquals('registerPostModifier', $methodCalls[0][0]);
        $this->assertEquals('registerPostModifier', $methodCalls[1][0]);

        $this->assertEquals('registerPreModifier', $methodCalls[2][0]);
        $this->assertEquals('registerPreModifier', $methodCalls[3][0]);

        $this->assertEquals(array(new Reference('validation_modifier_service')),        $methodCalls[0][1]);
        $this->assertEquals(array(new Reference('value_optimizer_modifier_service')),   $methodCalls[1][1]);

        $this->assertEquals(array(new Reference('pre_modifier_service')),   $methodCalls[2][1]);
        $this->assertEquals(array(new Reference('pre2_modifier_service')),  $methodCalls[3][1]);
    }

    public function testTaggedModifierSorting()
    {
        $validationModifier = new Definition('ValidatorClass');
        $validationModifier->addTag('rollerworks_record_filter.formatter_post_modifier', array('priority' => 1));

        $valueOptimizerModifier = new Definition('ValueOptimizerClass');
        $valueOptimizerModifier->addTag('rollerworks_record_filter.formatter_post_modifier', array('priority' => 0));

        $preModifier = new Definition('PreModifierClass');
        $preModifier->addTag('rollerworks_record_filter.formatter_pre_modifier', array('priority' => 1));

        $preModifier2 = new Definition('PreModifier2Class');
        $preModifier2->addTag('rollerworks_record_filter.formatter_pre_modifier', array('priority' => 0));

        $modifiersRegistry = new Definition('Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry');

        $container = $this->createContainer();
        $container->setDefinition('validation_modifier_service',        $validationModifier);
        $container->setDefinition('value_optimizer_modifier_service',   $valueOptimizerModifier);

        $container->setDefinition('pre2_modifier_service',    $preModifier2);
        $container->setDefinition('pre_modifier_service',     $preModifier);

        $container->setDefinition('rollerworks_record_filter.formatter_factory.modifiers_registry', $modifiersRegistry);

        $profilerPass = new FormatterModifiersRegistryPass();
        $profilerPass->process($container);

        $methodCalls = $container->getDefinition('rollerworks_record_filter.formatter_factory.modifiers_registry')->getMethodCalls();

        $this->assertEquals(4, count($methodCalls));

        $this->assertEquals('registerPostModifier', $methodCalls[0][0]);
        $this->assertEquals('registerPostModifier', $methodCalls[1][0]);

        $this->assertEquals(array(new Reference('validation_modifier_service')),         $methodCalls[0][1]);
        $this->assertEquals(array(new Reference('value_optimizer_modifier_service')),    $methodCalls[1][1]);

        $this->assertEquals(array(new Reference('pre_modifier_service')),     $methodCalls[2][1]);
        $this->assertEquals(array(new Reference('pre2_modifier_service')),    $methodCalls[3][1]);
    }
}
