<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\FieldLabelResolver;

use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;

class ChainFieldLabelResolver implements FieldLabelResolverInterface
{
    /**
     * @var FieldLabelResolverInterface[]
     */
    private $resolvers;

    /**
     * @param FieldLabelResolverInterface[] $resolvers
     *
     * @throws UnexpectedTypeException when a resolver does not implement the FieldLabelResolverInterface
     */
    public function __construct(array $resolvers)
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof FieldLabelResolverInterface) {
                throw new UnexpectedTypeException($resolver, 'Rollerworks\Component\Search\FieldLabelResolverInterface');
            }
        }

        $this->resolvers = $resolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFieldLabel(FieldSet $fieldSet, $fieldName)
    {
        foreach ($this->resolvers as $resolver) {
            $fieldLabel = $resolver->resolveFieldLabel($fieldSet, $fieldName);

            if (null === $fieldLabel) {
                continue;
            }

            if ($fieldLabel !== $fieldName) {
                return $fieldLabel;
            }
        }

        return $fieldName;
    }
}
