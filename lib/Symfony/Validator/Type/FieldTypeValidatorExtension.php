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

namespace Rollerworks\Component\Search\Extension\Symfony\Validator\Type;

use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class FieldTypeValidatorExtension extends AbstractFieldTypeExtension
{
    public function getExtendedType(): string
    {
        return SearchFieldType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Constraint should always be converted to an array
        $constraintsNormalizer = static fn (Options $options, $constraints) => \is_object($constraints) ? [$constraints] : (array) $constraints;

        $resolver->setDefault('constraints', []);
        $resolver->setDefault('pattern_match_constraints', []);
        $resolver->setNormalizer('constraints', $constraintsNormalizer);
        $resolver->setNormalizer('pattern_match_constraints', $constraintsNormalizer);
    }
}
