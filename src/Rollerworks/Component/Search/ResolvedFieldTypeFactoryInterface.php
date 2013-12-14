<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ResolvedFieldTypeFactoryInterface
{
    /**
     * Resolves a form type.
     *
     * @param FieldTypeInterface         $type
     * @param array                      $typeExtensions
     * @param ResolvedFieldTypeInterface $parent
     *
     * @return ResolvedFieldTypeInterface
     *
     * @throws Exception\UnexpectedTypeException  if the types parent {@link FormTypeInterface::getParent()} is not a string
     * @throws Exception\InvalidArgumentException if the types parent can not be retrieved from any extension
     */
    public function createResolvedType(FieldTypeInterface $type, array $typeExtensions, ResolvedFieldTypeInterface $parent = null);
}
