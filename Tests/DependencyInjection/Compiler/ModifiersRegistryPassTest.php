<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        $validationModifier->addTag('rollerworks_framework.record_filter.formatter_post_modifier');

        $valueOptimizerModifier = new Definition('ValueOptimizerClass');
        $valueOptimizerModifier->addTag('rollerworks_framework.record_filter.formatter_post_modifier');

        $preModifier = new Definition('PreModifierClass');
        $preModifier->addTag('rollerworks_framework.record_filter.formatter_pre_modifier');

        $preModifier2 = new Definition('PreModifier2Class');
        $preModifier2->addTag('rollerworks_framework.record_filter.formatter_pre_modifier');

        $modifiersRegistry = new Definition('Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry');

        $container = $this->createContainer();
        $container->setDefinition('validation_modifier_service',        $validationModifier);
        $container->setDefinition('value_optimizer_modifier_service',   $valueOptimizerModifier);

        $container->setDefinition('pre_modifier_service',   $preModifier);
        $container->setDefinition('pre2_modifier_service',  $preModifier2);

        $container->setDefinition('rollerworks_framework.record_filter.formatter_factory.modifiers_registry', $modifiersRegistry);

        $profilerPass = new FormatterModifiersRegistryPass();
        $profilerPass->process($container);

        $methodCalls = $container->getDefinition('rollerworks_framework.record_filter.formatter_factory.modifiers_registry')->getMethodCalls();

        $this->assertEquals(4, count($methodCalls));

        $this->assertEquals('addPostModifier', $methodCalls[0][0]);
        $this->assertEquals('addPostModifier', $methodCalls[1][0]);

        $this->assertEquals('addPreModifier', $methodCalls[2][0]);
        $this->assertEquals('addPreModifier', $methodCalls[3][0]);

        $this->assertEquals(array(new Reference('validation_modifier_service')),        $methodCalls[0][1]);
        $this->assertEquals(array(new Reference('value_optimizer_modifier_service')),   $methodCalls[1][1]);

        $this->assertEquals(array(new Reference('pre_modifier_service')),   $methodCalls[2][1]);
        $this->assertEquals(array(new Reference('pre2_modifier_service')),  $methodCalls[3][1]);
    }

    public function testTaggedModifierSorting()
    {
        $validationModifier = new Definition('ValidatorClass');
        $validationModifier->addTag('rollerworks_framework.record_filter.formatter_post_modifier', array('priority' => 1));

        $valueOptimizerModifier = new Definition('ValueOptimizerClass');
        $valueOptimizerModifier->addTag('rollerworks_framework.record_filter.formatter_post_modifier', array('priority' => 0));

        $preModifier = new Definition('PreModifierClass');
        $preModifier->addTag('rollerworks_framework.record_filter.formatter_pre_modifier', array('priority' => 1));

        $preModifier2 = new Definition('PreModifier2Class');
        $preModifier2->addTag('rollerworks_framework.record_filter.formatter_pre_modifier', array('priority' => 0));

        $modifiersRegistry = new Definition('Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry');

        $container = $this->createContainer();
        $container->setDefinition('validation_modifier_service',        $validationModifier);
        $container->setDefinition('value_optimizer_modifier_service',   $valueOptimizerModifier);

        $container->setDefinition('pre2_modifier_service',    $preModifier2);
        $container->setDefinition('pre_modifier_service',     $preModifier);

        $container->setDefinition('rollerworks_framework.record_filter.formatter_factory.modifiers_registry', $modifiersRegistry);

        $profilerPass = new FormatterModifiersRegistryPass();
        $profilerPass->process($container);

        $methodCalls = $container->getDefinition('rollerworks_framework.record_filter.formatter_factory.modifiers_registry')->getMethodCalls();

        $this->assertEquals(4, count($methodCalls));

        $this->assertEquals('addPostModifier', $methodCalls[0][0]);
        $this->assertEquals('addPostModifier', $methodCalls[1][0]);

        $this->assertEquals(array(new Reference('validation_modifier_service')),         $methodCalls[0][1]);
        $this->assertEquals(array(new Reference('value_optimizer_modifier_service')),    $methodCalls[1][1]);

        $this->assertEquals(array(new Reference('pre_modifier_service')),     $methodCalls[2][1]);
        $this->assertEquals(array(new Reference('pre2_modifier_service')),    $methodCalls[3][1]);
    }
}
