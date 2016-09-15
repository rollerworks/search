<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\FieldAliasResolver;

use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldSet;

class ChainFieldAliasResolver implements FieldAliasResolverInterface
{
    /**
     * @var FieldAliasResolverInterface[]
     */
    private $resolvers;

    /**
     * @param FieldAliasResolverInterface[] $resolvers
     *
     * @throws UnexpectedTypeException when a resolver does not implement the FieldAliasResolverInterface
     */
    public function __construct(array $resolvers)
    {
        foreach ($resolvers as $resolver) {
            if (!$resolver instanceof FieldAliasResolverInterface) {
                throw new UnexpectedTypeException($resolver, 'Rollerworks\Component\Search\FieldAliasResolverInterface');
            }
        }

        $this->resolvers = $resolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveFieldName(FieldSet $fieldSet, $fieldAlias)
    {
        foreach ($this->resolvers as $resolver) {
            $fieldName = $resolver->resolveFieldName($fieldSet, $fieldAlias);

            if (null === $fieldName) {
                continue;
            }

            if ($fieldName !== $fieldAlias) {
                return $fieldName;
            }
        }

        return $fieldAlias;
    }
}
