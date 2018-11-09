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

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Value\PatternMatch;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class TextType extends AbstractFieldType
{
    public function buildType(FieldConfig $config, array $options): void
    {
        $config->setValueTypeSupport(PatternMatch::class, true);
    }

    public function getBlockPrefix(): string
    {
        return 'text';
    }
}
