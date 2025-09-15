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

use Rollerworks\Component\Search\Extension\Core\DataTransformer\BooleanToLocalizedValueTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\BooleanToNormValueTransformer;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class BooleanType extends AbstractFieldType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('view_label', static function (OptionsResolver $options): void {
            $options->setDefaults([
                'true' => 'yes',
                'false' => 'no',
            ]);

            $options->setAllowedTypes('true', ['string']);
            $options->setAllowedTypes('false', ['string']);
        });

        $resolver->setDefault('norm_label', static function (OptionsResolver $options): void {
            $options->setDefaults([
                'true' => 'true',
                'false' => 'false',
            ]);

            $options->setAllowedTypes('true', ['string']);
            $options->setAllowedTypes('false', ['string']);
        });

        $resolver->setDefault('view_aliases', static function (OptionsResolver $options, Options $parent): void {
            $options->setDefaults([
                'true' => array_unique(['yes', 'y', '1', 1, 'on', 'true', $parent['view_label']['true']], \SORT_REGULAR),
                'false' => array_unique(['no', 'n', '0', 0, 'off', 'false', $parent['view_label']['false']], \SORT_REGULAR),
            ]);

            $options->setAllowedTypes('true', ['scalar[]']);
            $options->setAllowedTypes('false', ['scalar[]']);
        });

        $resolver->setDefault('invalid_message', 'This value is not a valid boolean.');
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        $config->setNormTransformer(new BooleanToNormValueTransformer($options['norm_label']['true'], $options['norm_label']['false']));
        $config->setViewTransformer(
            new BooleanToLocalizedValueTransformer(
                $options['view_label']['true'],
                $options['view_label']['false'],
                $options['view_aliases']['true'],
                $options['view_aliases']['false'],
            ),
        );
    }

    public function getBlockPrefix(): string
    {
        return 'boolean';
    }
}
