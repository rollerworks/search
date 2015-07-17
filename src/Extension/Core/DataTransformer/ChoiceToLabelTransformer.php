<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceListInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChoiceToLabelTransformer implements DataTransformerInterface
{
    private $choiceList;

    /**
     * Constructor.
     *
     * @param ChoiceListInterface $choiceList
     */
    public function __construct(ChoiceListInterface $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($choice)
    {
        return (string) $this->choiceList->getLabelForChoice($choice);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null !== $value && !is_scalar($value)) {
            throw new TransformationFailedException('Expected a scalar.');
        }

        // These are now valid ChoiceList values, so we can return null
        // right away.
        if ('' === $value || null === $value) {
            return;
        }

        $choice = $this->choiceList->getChoiceForLabel($value);

        if (null === $choice) {
            throw new TransformationFailedException(
                sprintf('The choice "%s" does not exist.', $value)
            );
        }

        return '' === $choice ? null : $choice;
    }
}
