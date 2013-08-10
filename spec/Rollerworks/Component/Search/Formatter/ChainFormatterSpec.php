<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Formatter;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\ValuesGroup;

class ChainFormatterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Formatter\ChainFormatter');
        $this->shouldImplement('Rollerworks\Component\Search\FormatterInterface');
    }

    function it_should_have_no_formatters_by_default()
    {
        $this->getFormatters()->shouldHaveCount(0);
    }

    function it_should_allow_adding_formatters(FormatterInterface $formatter)
    {
        $this->addFormatter($formatter)->shouldReturnAnInstanceOf('Rollerworks\Component\Search\Formatter\ChainFormatter');
        $this->getFormatters()->shouldReturn(array($formatter));
    }

    function it_should_execute_the_registered_formatters(FieldSet $fieldSet, ValuesGroup $valuesGroup, FormatterInterface $formatter, FormatterInterface $formatter2)
    {
        $valuesGroup->hasViolations()->willReturn(false);
        $formatter->format($fieldSet, $valuesGroup)->shouldBeCalled();
        $formatter2->format($fieldSet, $valuesGroup)->shouldBeCalled();

        $this->addFormatter($formatter);
        $this->addFormatter($formatter2);

        $this->format($fieldSet, $valuesGroup);
    }

    function it_should_not_execution_when_ValuesGroup_has_violations(FieldSet $fieldSet, ValuesGroup $valuesGroup, FormatterInterface $formatter, FormatterInterface $formatter2)
    {
        $valuesGroup->hasViolations()->willReturn(true);

        $formatter->format($fieldSet, $valuesGroup)->shouldNotBeCalled();
        $formatter2->format($fieldSet, $valuesGroup)->shouldNotBeCalled();

        $this->addFormatter($formatter);
        $this->addFormatter($formatter2);

        $this->format($fieldSet, $valuesGroup);
    }

    function it_should_stop_execution_if_a_formatter_sets_violations(FieldSet $fieldSet, ValuesGroup $valuesGroup, FormatterInterface $formatter, FormatterInterface $formatter2)
    {
        $valuesGroup->hasViolations()->willReturn(false);

        $formatter->format($fieldSet, $valuesGroup)->will(function() use ($valuesGroup) {
            $valuesGroup->hasViolations()->willReturn(true);
        });
        $formatter2->format($fieldSet, $valuesGroup)->shouldNotBeCalled();

        $this->addFormatter($formatter);
        $this->addFormatter($formatter2);

        $this->format($fieldSet, $valuesGroup);
    }

    function it_should_complain_when_adding_its_own_instance(FormatterInterface $formatter)
    {
        $this->addFormatter($formatter);

        $this->shouldThrow(new \InvalidArgumentException('Unable to add formatter to chain, can not assign formatter to its self.'))->duringAddFormatter($this);
    }
}
