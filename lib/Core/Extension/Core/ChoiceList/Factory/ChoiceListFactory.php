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

/**
 * Creates {@link ChoiceList} instances.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ChoiceListFactory
{
    /**
     * Creates a choice list for the given choices.
     *
     * The choices should be passed in the values of the choices array.
     *
     * Optionally, a callable can be passed for generating the choice values.
     * The callable receives the choice as first and the array key as the second
     * argument.
     *
     * @param array|\Traversable $choices The choices
     * @param null|callable      $value   The callable generating the choice
     *                                    values
     */
    public function createListFromChoices($choices, $value = null): ChoiceList;

    /**
     * Creates a choice list that is loaded with the given loader.
     *
     * Optionally, a callable can be passed for generating the choice values.
     * The callable receives the choice as first and the array key as the second
     * argument.
     *
     * @param ChoiceLoader  $loader The choice loader
     * @param null|callable $value  The callable generating the choice
     *                              values
     */
    public function createListFromLoader(ChoiceLoader $loader, $value = null): ChoiceList;

    /**
     * Creates a view for the given choice list.
     *
     * Callables may be passed for all optional arguments. The callables receive
     * the choice as first and the array key as the second argument.
     *
     *  * The callable for the label and the name should return the generated
     *    label/choice name.
     *  * The callable for the preferred choices should return true or false,
     *    depending on whether the choice should be preferred or not.
     *  * The callable for the grouping should return the group name or null if
     *    a choice should not be grouped.
     *  * The callable for the attributes should return an array of HTML
     *    attributes that will be inserted in the tag of the choice.
     *
     * If no callable is passed, the labels will be generated from the choice
     * keys. The view indices will be generated using an incrementing integer
     * by default.
     *
     * The preferred choices can also be passed as array. Each choice that is
     * contained in that array will be marked as preferred.
     *
     * The attributes can be passed as multi-dimensional array. The keys should
     * match the keys of the choices. The values should be arrays of HTML
     * attributes that should be added to the respective choice.
     *
     * @param ChoiceList          $list             The choice list
     * @param null|array|callable $preferredChoices The preferred choices
     * @param null|callable       $label            The callable generating the
     *                                              choice labels
     * @param null|callable       $index            The callable generating the
     *                                              view indices
     * @param null|callable       $groupBy          The callable generating the
     *                                              group names
     * @param null|array|callable $attr             The callable generating the
     *                                              HTML attributes
     */
    public function createView(ChoiceList $list, $preferredChoices = null, $label = null, $index = null, $groupBy = null, $attr = null): ChoiceListView;
}
