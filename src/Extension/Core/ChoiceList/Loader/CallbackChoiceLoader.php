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

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader;

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ArrayChoiceList;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;

/**
 * Loads an {@link ArrayChoiceList} instance from a callable returning an array of choices.
 *
 * @author Jules Pietri <jules@heahprod.com>
 */
final class CallbackChoiceLoader implements ChoiceLoader
{
    private $callback;

    /**
     * The loaded choice list.
     *
     * @var ArrayChoiceList
     */
    private $choiceList;

    /**
     * @var bool
     */
    private $valuesAreConstant;

    /**
     * @param callable $callback          The callable returning an array of choices
     * @param bool     $valuesAreConstant Indicate whether values are constant
     *                                    (not dependent of there position)
     */
    public function __construct(callable $callback, bool $valuesAreConstant = false)
    {
        $this->callback = $callback;
        $this->valuesAreConstant = $valuesAreConstant;
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoiceList(callable $value = null): ChoiceList
    {
        if (null !== $this->choiceList) {
            return $this->choiceList;
        }

        return $this->choiceList = new ArrayChoiceList(call_user_func($this->callback), $value);
    }

    /**
     * {@inheritdoc}
     */
    public function loadChoicesForValues(array $values, callable $value = null): array
    {
        // Optimize
        if (empty($values)) {
            return [];
        }

        return $this->loadChoiceList($value)->getChoicesForValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function loadValuesForChoices(array $choices, callable $value = null): array
    {
        // Optimize
        if (empty($choices)) {
            return [];
        }

        return $this->loadChoiceList($value)->getValuesForChoices($choices);
    }

    /**
     * {@inheritdoc}
     */
    public function isValuesConstant(): bool
    {
        return $this->valuesAreConstant;
    }
}
