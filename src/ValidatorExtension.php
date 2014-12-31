<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Symfony\Validator;

use Rollerworks\Component\Search\AbstractExtension;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValidatorExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadTypeExtensions()
    {
        return array(
            new Type\FieldTypeValidatorExtension(),
        );
    }
}
