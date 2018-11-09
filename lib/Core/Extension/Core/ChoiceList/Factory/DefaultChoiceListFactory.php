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

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\LazyChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceGroupView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceListView;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\View\ChoiceView;

/**
 * Default implementation of {@link ChoiceListFactory}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class DefaultChoiceListFactory implements ChoiceListFactory
{
    public function createListFromChoices($choices, $value = null): ChoiceList
    {
        return new ArrayChoiceList($choices, $value);
    }

    public function createListFromLoader(ChoiceLoader $loader, $value = null): ChoiceList
    {
        return new LazyChoiceList($loader, $value);
    }

    public function createView(ChoiceList $list, $preferredChoices = null, $label = null, $index = null, $groupBy = null, $attr = null): ChoiceListView
    {
        $preferredViews = [];
        $otherViews = [];
        $choices = $list->getChoices();
        $keys = $list->getOriginalKeys();

        if (!\is_callable($preferredChoices) && !empty($preferredChoices)) {
            $preferredChoices = function ($choice) use ($preferredChoices) {
                return false !== array_search($choice, $preferredChoices, true);
            };
        }

        // The names are generated from an incrementing integer by default
        if (null === $index) {
            $index = 0;
        }

        // If $groupBy is a callable, choices are added to the group with the
        // name returned by the callable. If the callable returns null, the
        // choice is not added to any group
        if (\is_callable($groupBy)) {
            foreach ($choices as $value => $choice) {
                self::addChoiceViewGroupedBy(
                    $groupBy,
                    $choice,
                    (string) $value,
                    $label,
                    $keys,
                    $index,
                    $attr,
                    $preferredChoices,
                    $preferredViews,
                    $otherViews
                );
            }
        } else {
            // Otherwise use the original structure of the choices
            self::addChoiceViewsGroupedBy(
                $list->getStructuredValues(),
                $label,
                $choices,
                $keys,
                $index,
                $attr,
                $preferredChoices,
                $preferredViews,
                $otherViews
            );
        }

        // Remove any empty group view that may have been created by
        // addChoiceViewGroupedBy()
        foreach ($preferredViews as $key => $view) {
            if ($view instanceof ChoiceGroupView && 0 === \count($view->choices)) {
                unset($preferredViews[$key]);
            }
        }

        foreach ($otherViews as $key => $view) {
            if ($view instanceof ChoiceGroupView && 0 === \count($view->choices)) {
                unset($otherViews[$key]);
            }
        }

        return new ChoiceListView($otherViews, $preferredViews);
    }

    private static function addChoiceView($choice, $value, $label, $keys, &$index, $attr, $isPreferred, &$preferredViews, &$otherViews): void
    {
        // $value may be an integer or a string, since it's stored in the array
        // keys. We want to guarantee it's a string though.
        $key = $keys[$value];
        $nextIndex = \is_int($index) ? $index++ : \call_user_func($index, $choice, $key, $value);

        // BC normalize label to accept a false value
        if (null === $label) {
            // If the labels are null, use the original choice key by default
            $label = (string) $key;
        } elseif (false !== $label) {
            // If "choice_label" is set to false and "expanded" is true, the value false
            // should be passed on to the "label" option of the checkboxes/radio buttons
            $dynamicLabel = \call_user_func($label, $choice, $key, $value);
            $label = false === $dynamicLabel ? false : (string) $dynamicLabel;
        }

        $view = new ChoiceView(
            $choice,
            $value,
            $label,
            // The attributes may be a callable or a mapping from choice indices
            // to nested arrays
            \is_callable($attr) ? \call_user_func($attr, $choice, $key, $value) : ($attr[$key] ?? [])
        );

        // $isPreferred may be null if no choices are preferred
        if ($isPreferred && \call_user_func($isPreferred, $choice, $key, $value)) {
            $preferredViews[$nextIndex] = $view;
        } else {
            $otherViews[$nextIndex] = $view;
        }
    }

    private static function addChoiceViewsGroupedBy($groupBy, $label, $choices, $keys, &$index, $attr, $isPreferred, &$preferredViews, &$otherViews): void
    {
        foreach ($groupBy as $key => $value) {
            if (null === $value) {
                continue;
            }

            // Add the contents of groups to new ChoiceGroupView instances
            if (\is_array($value)) {
                $preferredViewsForGroup = [];
                $otherViewsForGroup = [];

                self::addChoiceViewsGroupedBy(
                    $value,
                    $label,
                    $choices,
                    $keys,
                    $index,
                    $attr,
                    $isPreferred,
                    $preferredViewsForGroup,
                    $otherViewsForGroup
                );

                if (\count($preferredViewsForGroup) > 0) {
                    $preferredViews[$key] = new ChoiceGroupView($key, $preferredViewsForGroup);
                }

                if (\count($otherViewsForGroup) > 0) {
                    $otherViews[$key] = new ChoiceGroupView($key, $otherViewsForGroup);
                }

                continue;
            }

            // Add ungrouped items directly
            self::addChoiceView(
                $choices[$value],
                $value,
                $label,
                $keys,
                $index,
                $attr,
                $isPreferred,
                $preferredViews,
                $otherViews
            );
        }
    }

    private static function addChoiceViewGroupedBy($groupBy, $choice, $value, $label, $keys, &$index, $attr, $isPreferred, &$preferredViews, &$otherViews): void
    {
        $groupLabel = \call_user_func($groupBy, $choice, $keys[$value], $value);

        if (null === $groupLabel) {
            // If the callable returns null, don't group the choice
            self::addChoiceView(
                $choice,
                $value,
                $label,
                $keys,
                $index,
                $attr,
                $isPreferred,
                $preferredViews,
                $otherViews
            );

            return;
        }

        $groupLabel = (string) $groupLabel;

        // Initialize the group views if necessary. Unnecessarily built group
        // views will be cleaned up at the end of createView()
        if (!isset($preferredViews[$groupLabel])) {
            $preferredViews[$groupLabel] = new ChoiceGroupView($groupLabel);
            $otherViews[$groupLabel] = new ChoiceGroupView($groupLabel);
        }

        self::addChoiceView(
            $choice,
            $value,
            $label,
            $keys,
            $index,
            $attr,
            $isPreferred,
            $preferredViews[$groupLabel]->choices,
            $otherViews[$groupLabel]->choices
        );
    }
}
