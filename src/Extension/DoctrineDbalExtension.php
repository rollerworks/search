<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
