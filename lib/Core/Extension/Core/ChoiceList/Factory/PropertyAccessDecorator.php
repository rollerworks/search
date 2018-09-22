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

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList\Factory;

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

/**
 * Adds property path support to a choice list factory.
 *
 * Pass the decorated factory to the constructor:
 *
 * ```php
 * $decorator = new PropertyAccessDecorator($factory);
 * ```
 *
 * You can now pass property paths for generating choice values, labels, view
 * indices, HTML attributes and for determining the preferred choices and the
 * choice groups:
 *
 * ```php
 * // extract values from the $value property
 * $list = $createListFromChoices($objects, 'value');
 * ```
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class PropertyAccessDecorator implements ChoiceListFactory
{
    private $decoratedFactory;
    private $propertyAccessor;

    /**
     * Decorates the given factory.
     *
     * @param ChoiceListFactory     $decoratedFactory The decorated factory
     * @param PropertyAccessor|null $propertyAccessor The used property accessor
     */
    public function __construct(ChoiceListFactory $decoratedFactory, ?PropertyAccessor $propertyAccessor = null)
    {
        $this->decoratedFactory = $decoratedFactory;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * Returns the decorated factory.
     *
     * @return ChoiceListFactory
     */
    public function getDecoratedFactory(): ChoiceListFactory
    {
        return $this->decoratedFactory;
    }

    /**
     * @inheritdoc
     *
     * @param array|\Traversable                $choices The choices
     * @param callable|string|PropertyPath|null $value   The callable or path for
     *                                                   generating the choice values
     *
     * @return ChoiceList
     */
    public function createListFromChoices($choices, $value = null): ChoiceList
    {
        if (\is_string($value)) {
            $value = new PropertyPath($value);
        }

        if ($value instanceof PropertyPath) {
            $accessor = $this->propertyAccessor;
            $value = function ($choice) use ($accessor, $value) {
                // The callable may be invoked with a non-object/array value
                // when such values are passed to
                // ChoiceList::getValuesForChoices(). Handle this case
                // so that the call to getValue() doesn't break.
                if (\is_object($choice) || \is_array($choice)) {
                    return $accessor->getValue($choice, $value);
                }
            };
        }

        return $this->decoratedFactory->createListFromChoices($choices, $value);
    }

    /**
     * @inheritdoc
     *
     * @param ChoiceLoader                      $loader The choice loader
     * @param callable|string|PropertyPath|null $value  The callable or path for
     *                                                  generating the choice values
     *
     * @return ChoiceList
     */
    public function createListFromLoader(ChoiceLoader $loader, $value = null): ChoiceList
    {
        if (\is_string($value)) {
            $value = new PropertyPath($value);
        }

        if ($value instanceof PropertyPath) {
            $accessor = $this->propertyAccessor;
            $value = function ($choice) use ($accessor, $value) {
                // The callable may be invoked with a non-object/array value
                // when such values are passed to
                // ChoiceList::getValuesForChoices(). Handle this case
                // so that the call to getValue() doesn't break.
                if (\is_object($choice) || \is_array($choice)) {
                    return $accessor->getValue($choice, $value);
                }
            };
        }

        return $this->decoratedFactory->createListFromLoader($loader, $value);
    }

    /**
     * @inheritdoc
     *
     * @param ChoiceList                              $list             The choice list
     * @param null|array|callable|string|PropertyPath $preferredChoices The preferred choices
     * @param null|callable|string|PropertyPath       $label            The callable or path generating the choice labels
     * @param null|callable|string|PropertyPath       $index            The callable or path generating the view indices
     * @param null|callable|string|PropertyPath       $groupBy          The callable or path generating the group names
     * @param null|array|callable|string|PropertyPath $attr             The callable or path generating the HTML attributes
     *
     * @return ChoiceListView
     */
    public function createView(ChoiceList $list, $preferredChoices = null, $label = null, $index = null, $groupBy = null, $attr = null): ChoiceListView
    {
        $accessor = $this->propertyAccessor;

        if (\is_string($label)) {
            $label = new PropertyPath($label);
        }

        if ($label instanceof PropertyPath) {
            $label = function ($choice) use ($accessor, $label) {
                return $accessor->getValue($choice, $label);
            };
        }

        if (\is_string($preferredChoices)) {
            $preferredChoices = new PropertyPath($preferredChoices);
        }

        if ($preferredChoices instanceof PropertyPath) {
            $preferredChoices = function ($choice) use ($accessor, $preferredChoices) {
                try {
                    return $accessor->getValue($choice, $preferredChoices);
                } catch (UnexpectedTypeException $e) {
                    // Assume not preferred if not readable
                    return false;
                }
            };
        }

        if (\is_string($index)) {
            $index = new PropertyPath($index);
        }

        if ($index instanceof PropertyPath) {
            $index = function ($choice) use ($accessor, $index) {
                return $accessor->getValue($choice, $index);
            };
        }

        if (\is_string($groupBy)) {
            $groupBy = new PropertyPath($groupBy);
        }

        if ($groupBy instanceof PropertyPath) {
            $groupBy = function ($choice) use ($accessor, $groupBy) {
                try {
                    return $accessor->getValue($choice, $groupBy);
                } catch (UnexpectedTypeException $e) {
                    // Don't group if path is not readable
                }
            };
        }

        if (\is_string($attr)) {
            $attr = new PropertyPath($attr);
        }

        if ($attr instanceof PropertyPath) {
            $attr = function ($choice) use ($accessor, $attr) {
                return $accessor->getValue($choice, $attr);
            };
        }

        return $this->decoratedFactory->createView($list, $preferredChoices, $label, $index, $groupBy, $attr);
    }
}
