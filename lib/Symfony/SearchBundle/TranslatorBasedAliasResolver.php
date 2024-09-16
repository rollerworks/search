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

namespace Rollerworks\Bundle\SearchBundle;

use Rollerworks\Component\Search\Field\FieldConfig;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatorBasedAliasResolver
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(FieldConfig $field)
    {
        $label = $field->getOption('label');

        if ($label === null) {
            return $field->getName();
        }

        if ($label instanceof TranslatableInterface) {
            return $label->trans($this->translator);
        }

        return $label;
    }
}
