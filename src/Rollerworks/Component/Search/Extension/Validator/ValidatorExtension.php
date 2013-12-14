<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Validator;

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
            new Type\FieldTypeExtension(),
        );
    }
}
