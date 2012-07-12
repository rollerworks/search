<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * TypeChain.
 *
 * Allows using multiple filter-types on an field.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class TypeChain implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface
{
    /**
     * @var ChainableTypeInterface[]
     */
    protected $types = array();

    /**
     * @var FilterTypeInterface
     */
    protected $acceptedType;

    /**
     * @var string
     */
    protected $lastResult;

    /**
     * Constructor.
     *
     * @param ChainableTypeInterface[] $types
     */
    public function __construct($types)
    {
        foreach ($types as $name => $type) {
            $this->set($type, $name);
        }
    }

    /**
     * Set/add an type on the chain.
     *
     * @param ChainableTypeInterface $type
     * @param integer|string         $name Optional name
     *
     * @return TypeChain
     */
    public function set(ChainableTypeInterface $type, $name)
    {
        $this->types[$name] = $type;

        return $this;
    }

    /**
     * Returns the type by name.
     *
     * @param string|integer $name
     *
     * @return ChainableTypeInterface
     *
     * @throws \InvalidArgumentException
     */
    public function get($name)
    {
        if (!isset($this->types[$name])) {
            throw new \InvalidArgumentException(sprintf('Type named "%s" is not registered in this chain.', $name));
        }

        return $this->types[$name];
    }

    /**
     * Returns all the registered types.
     *
     * @return ChainableTypeInterface[]
     */
    public function all()
    {
        return $this->types;
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $input
     *
     * @return DecoratedValue
     */
    public function sanitizeString($input)
    {
        if ($input !== $this->lastResult && !$this->validateValue($input)) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        $value = $this->acceptedType->sanitizeString($input);

        return new DecoratedValue($value, $this->acceptedType);
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $value
     */
    public function formatOutput($value)
    {
        if (!$value instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        return $value->getType()->formatOutput($value->getValue());
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $input
     */
    public function dumpValue($input)
    {
        if (!$input instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        return $input->getType()->dumpValue($input->getValue());
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $input
     * @param DecoratedValue $nextValue
     */
    public function isHigher($input, $nextValue)
    {
        if (!$input instanceof DecoratedValue || !$nextValue instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        if ($nextValue->getType() !== $input->getType()) {
            return false;
        }

        return $input->getType()->isHigher($input->getValue(), $nextValue->getValue());
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $input
     * @param DecoratedValue $nextValue
     */
    public function isLower($input, $nextValue)
    {
        if (!$input instanceof DecoratedValue || !$nextValue instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        if ($nextValue->getType() !== $input->getType()) {
            return false;
        }

        return $input->getType()->isLower($input->getValue(), $nextValue->getValue());
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $input
     * @param DecoratedValue $nextValue
     */
    public function isEqual($input, $nextValue)
    {
        if (!$input instanceof DecoratedValue || !$nextValue instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        if ($nextValue->getType() !== $input->getType()) {
            return false;
        }

        return $input->getType()->isEqual($input->getValue(), $nextValue->getValue());
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        foreach ($this->types as $type) {
            if ($type->acceptInput($input)) {

                $this->lastResult = $input;
                $this->acceptedType = $type;

                return $type->validateValue($input, $message, $messageBag);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        $matcher = '(?:';

        foreach ($this->types as $type) {
            if (!$type instanceof ValueMatcherInterface) {
                continue;
            }

            $matcher .= $type->getMatcherRegex() . '|' ;
        }

        $matcher = rtrim($matcher, '|');

        if ('(?:' === $matcher) {
            return null;
        }

        $matcher .= ')';

        return $matcher;
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $first
     * @param DecoratedValue $second
     */
    public function sortValuesList($first, $second)
    {
        if (!$first instanceof DecoratedValue || !$second instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        if ($first->getType() !== $second->getType()) {
            return false;
        }

        if (!$first->getType() instanceof ValuesToRangeInterface) {
            return null;
        }

        return $first->getType()->sortValuesList($first->getValue(), $second->getValue());
    }

    /**
     * {@inheritdoc}
     *
     * @param DecoratedValue $input
     */
    public function getHigherValue($input)
    {
        if (!$input instanceof DecoratedValue) {
            throw new \InvalidArgumentException('Value must be an DecoratedValue object.');
        }

        if (!$input->getType() instanceof ValuesToRangeInterface) {
            return null;
        }

        return $input->getType()->getHigherValue($input->getValue());
    }
}
