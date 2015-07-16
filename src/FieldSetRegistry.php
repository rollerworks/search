<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

final class FieldSetRegistry implements FieldSetRegistryInterface
{
    private $fieldSets = [];

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return isset($this->fieldSets[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (isset($this->fieldSets[$name])) {
            return $this->fieldSets[$name];
        }

        throw new InvalidArgumentException(sprintf('Unable to get none registered FieldSet "%s".', $name));
    }

    /**
     * {@inheritdoc}
     */
    public function add(FieldSet $fieldSet)
    {
        $name = $fieldSet->getSetName();

        if (isset($this->fieldSets[$name])) {
            throw new InvalidArgumentException(sprintf('Unable to overwrite already registered FieldSet "%s".', $name));
        }

        if (!$fieldSet->isConfigLocked()) {
            throw new InvalidArgumentException(sprintf('Unable to register unlocked FieldSet "%s".', $name));
        }

        $this->fieldSets[$name] = $fieldSet;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->fieldSets;
    }
}
