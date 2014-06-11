<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * A choice list that is loaded lazily
 *
 * This list loads itself as soon as any of the getters is accessed for the
 * first time. You should implement loadChoiceList() in your child classes,
 * which should return a ChoiceListInterface instance.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class LazyChoiceList implements ChoiceListInterface
{
    /**
     * The loaded choice list
     *
     * @var ChoiceListInterface
     */
    private $choiceList;

    /**
     * {@inheritDoc}
     */
    public function getChoices()
    {
        if (!$this->choiceList) {
            $this->load();
        }

        return $this->choiceList->getChoices();
    }

    /**
     * {@inheritDoc}
     */
    public function getValues()
    {
        if (!$this->choiceList) {
            $this->load();
        }

        return $this->choiceList->getValues();
    }

    /**
     * {@inheritDoc}
     */
    public function getChoiceForValue($value)
    {
        if (!$this->choiceList) {
            $this->load();
        }

        return $this->choiceList->getChoiceForValue($value);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueForChoice($choices)
    {
        if (!$this->choiceList) {
            $this->load();
        }

        return $this->choiceList->getValueForChoice($choices);
    }

    /**
     * Loads the choice list.
     *
     * Should be implemented by child classes.
     *
     * @return ChoiceListInterface The loaded choice list
     */
    abstract protected function loadChoiceList();

    private function load()
    {
        $choiceList = $this->loadChoiceList();

        if (!$choiceList instanceof ChoiceListInterface) {
            throw new InvalidArgumentException(sprintf('loadChoiceList() should return a ChoiceListInterface instance. Got %s', gettype($choiceList)));
        }

        $this->choiceList = $choiceList;
    }
}
