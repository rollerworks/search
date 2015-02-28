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
     * @throws Exception\UnexpectedTypeException  if the types parent {@link FormTypeInterface::getParent()} is not a string
     * @throws Exception\InvalidArgumentException if the types parent can not be retrieved from any extension
     *
     * @return ResolvedFieldTypeInterface
     */
    public function createResolvedType(FieldTypeInterface $type, array $typeExtensions, ResolvedFieldTypeInterface $parent = null);
}
