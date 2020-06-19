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
use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatorBasedAliasResolver
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function __invoke(FieldConfig $field)
    {
        if (null !== $label = $field->getOption('label')) {
            return $label;
        }

        return $this->translator->trans(
            $field->getOption('label_template', $field->getName()),
            $field->getOption('label_parameters', []),
            $field->getOption('label_domain', 'search')
        );
    }
}
