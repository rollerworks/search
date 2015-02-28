<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ValuesBag;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class TextType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueTypeSupport(ValuesBag::VALUE_TYPE_PATTERN_MATCH, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'text';
    }
}
