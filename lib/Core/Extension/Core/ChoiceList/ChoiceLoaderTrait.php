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

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList;

/**
 * The ChoiceLoaderTrait can used for optimizing empty
 * choices/values in ChoiceLoaders.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
trait ChoiceLoaderTrait
{
    /**
     * @var ArrayChoiceList|null
     */
    protected $choiceList;

    public function loadChoicesForValues(array $values, ?callable $value = null): array
    {
        // Optimize
        if (empty($values)) {
            return [];
        }

        // If no callable is set, values are the same as choices
        if ($value === null) {
            return $values;
        }

        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    public function loadValuesForChoices(array $choices, ?callable $value = null): array
    {
        // Optimize
        if (empty($choices)) {
            return [];
        }

        // If no callable is set, choices are the same as values
        if ($value === null) {
            return $choices;
        }

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    /**
     * Loads a list of choices.
     *
     * @see \Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader::loadChoiceList
     */
    abstract public function loadChoiceList(?callable $value = null): ChoiceList;
}
