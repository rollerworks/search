<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace spec\Rollerworks\Component\Search;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\ResolvedFieldTypeInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Rollerworks\Component\Search\ValuesBag;

class SearchFieldSpec extends ObjectBehavior
{
    function let(ResolvedFieldTypeInterface $resolvedType)
    {
        $this->beConstructedWith('foobar', $resolvedType, ['name' => 'value']);
        $this->shouldImplement('Rollerworks\Component\Search\FieldConfigInterface');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\SearchField');
    }

    function it_should_have_a_name()
    {
        $this->getName()->shouldReturn('foobar');
    }

    function it_should_have_a_type(ResolvedFieldTypeInterface $resolvedType)
    {
        $this->getType()->shouldReturn($resolvedType);
    }

    function it_should_have_options()
    {
        $this->getOptions()->shouldReturn(['name' => 'value']);
    }

    function it_should_return_if_an_option_exists()
    {
        $this->hasOption('name')->shouldReturn(true);
        $this->hasOption('foo')->shouldReturn(false);
    }

    function it_should_return_an_options_value()
    {
        $this->getOption('name')->shouldReturn('value');
    }

    function it_should_return_null_by_default_if_the_option_does_exist()
    {
        $this->getOption('foo')->shouldReturn(null);
    }

    function it_should_return_default_value_if_the_option_does_exist()
    {
        $this->getOption('foo', 'value1')->shouldReturn('value1');
    }

    function it_supports_no_special_value_types_by_default()
    {
        $this->supportValueType(ValuesBag::VALUE_TYPE_RANGE)->shouldReturn(false);
        $this->supportValueType(ValuesBag::VALUE_TYPE_COMPARISON)->shouldReturn(false);
        $this->supportValueType(ValuesBag::VALUE_TYPE_PATTERN_MATCH)->shouldReturn(false);
    }

    function it_allows_configuring_value_support()
    {
        $this->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, true);
        $this->supportValueType(ValuesBag::VALUE_TYPE_RANGE)->shouldReturn(true);
        $this->supportValueType(ValuesBag::VALUE_TYPE_COMPARISON)->shouldReturn(false);

        // And now disable it
        $this->setValueTypeSupport(ValuesBag::VALUE_TYPE_RANGE, false);
        $this->supportValueType(ValuesBag::VALUE_TYPE_RANGE)->shouldReturn(false);
    }

    function it_should_have_no_model_reference_by_default()
    {
        $this->getModelRefClass()->shouldReturn(null);
        $this->getModelRefProperty()->shouldReturn(null);
    }

    function it_should_allow_setting_model_reference()
    {
        $this->setModelRef('User', 'id');

        $this->getModelRefClass()->shouldReturn('User');
        $this->getModelRefProperty()->shouldReturn('id');
    }

    function it_should_have_no_comparison_class_by_default()
    {
        $this->getValueComparison()->shouldReturn(null);
    }

    function it_should_allow_setting_a_comparison_class(ValueComparisonInterface $comparisonObj)
    {
        $this->setValueComparison($comparisonObj);
        $this->getValueComparison()->shouldReturn($comparisonObj);
    }

    function it_should_have_no_ViewTransformers_by_default()
    {
        $this->getViewTransformers()->shouldHaveCount(0);
    }

    function it_should_allow_adding_ViewTransformers(DataTransformerInterface $viewTransformer)
    {
        $this->addViewTransformer($viewTransformer);
        $this->getViewTransformers()->shouldReturn([$viewTransformer]);
    }

    function it_should_allow_resetting_ViewTransformers(DataTransformerInterface $viewTransformer)
    {
        $this->addViewTransformer($viewTransformer);
        $this->resetViewTransformers();

        $this->getViewTransformers()->shouldHaveCount(0);
    }

    function its_data_is_locked_by_default()
    {
        $this->getDataLocked()->shouldReturn(false);
    }

    function its_data_should_be_lockable()
    {
        $this->setDataLocked();
        $this->getDataLocked()->shouldReturn(true);
    }

    function its_data_should_not_be_changeable_when_lockable()
    {
        $this->setDataLocked();

        $this->shouldThrow(new BadMethodCallException('SearchField setter methods cannot be accessed anymore once the data is locked.'))->duringSetDataLocked();
    }
}
