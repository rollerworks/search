<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal;

use Rollerworks\Component\Search\AbstractExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\AgeDateConversion;

/**
 * Represents the doctrine dbal extension,
 * for the core Doctrine DBAL functionality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineDbalExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadTypeExtensions()
    {
        return array(
            new Type\FieldTypeExtension(),
            new Type\BirthdayTypeExtension(new AgeDateConversion()),
        );
    }
}
