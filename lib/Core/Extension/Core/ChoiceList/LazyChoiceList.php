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

use Rollerworks\Component\Search\Extension\Core\ChoiceList\Loader\ChoiceLoader;

/**
 * A choice list that loads its choices lazily.
 *
 * The choices are fetched using a {@link ChoiceLoaderInterface} instance.
 * If only {@link getChoicesForValues()} or {@link getValuesForChoices()} is
 * called, the choice list is only loaded partially for improved performance.
 *
 * Once {@link getChoices()} or {@link getValues()} is called, the list is
 * loaded fully.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class LazyChoiceList implements ChoiceList
{
    /**
     * @var ChoiceLoader
     */
    private $loader;

    /**
     * The callable creating string values for each choice.
     *
     * If null, choices are simply cast to strings.
     *
     * @var callable|null
     */
    private $value;

    /**
     * Optionally, a callable can be passed for generating the choice values.
     * The callable receives the choice as first and the array key as the second
     * argument.
     *
     * @param ChoiceLoader  $loader The choice loader
     * @param callable|null $value  The callable generating the choice
     *                              values
     */
    public function __construct(ChoiceLoader $loader, callable $value = null)
    {
        $this->loader = $loader;
        $this->value = $value;
    }

    public function getChoices(): array
    {
        return $this->loader->loadChoiceList($this->value)->getChoices();
    }

    public function getValues(): array
    {
        return $this->loader->loadChoiceList($this->value)->getValues();
    }

    public function getStructuredValues(): array
    {
        return $this->loader->loadChoiceList($this->value)->getStructuredValues();
    }

    public function getOriginalKeys(): array
    {
        return $this->loader->loadChoiceList($this->value)->getOriginalKeys();
    }

    public function getChoicesForValues(array $values): array
    {
        return $this->loader->loadChoicesForValues($values, $this->value);
    }

    public function getValuesForChoices(array $choices): array
    {
        return $this->loader->loadValuesForChoices($choices, $this->value);
    }

    public function isValuesConstant(): bool
    {
        return $this->loader->isValuesConstant();
    }
}
