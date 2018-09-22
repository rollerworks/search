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

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceList;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class ChoiceToValueTransformer implements DataTransformer
{
    private $choiceList;

    public function __construct(ChoiceList $choiceList)
    {
        $this->choiceList = $choiceList;
    }

    public function transform($choice)
    {
        $value = $this->choiceList->getValuesForChoices([$choice]);

        return (string) current($value);
    }

    public function reverseTransform($value)
    {
        if (null !== $value && !\is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        $choices = $this->choiceList->getChoicesForValues([(string) $value]);

        if (1 !== \count($choices)) {
            if (null === $value || '' === $value) {
                return;
            }

            throw new TransformationFailedException(sprintf('The choice "%s" does not exist or is not unique', $value));
        }

        return current($choices);
    }
}
