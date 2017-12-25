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

use Rollerworks\Component\Search\Extension\Core\ValueComparator\SimpleValueComparator;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Input\NormStringQueryInput;
use Rollerworks\Component\Search\Input\StringQueryInput;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SearchFieldType extends AbstractFieldType
{
    private $valueComparator;

    public function __construct()
    {
        $this->valueComparator = new SimpleValueComparator();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfig $config, array $options): void
    {
        $config->setValueComparator($this->valueComparator);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'translation_domain' => 'messages',
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                StringQueryInput::FIELD_LEXER_OPTION_NAME => null,
                NormStringQueryInput::FIELD_LEXER_OPTION_NAME => null,
                StringQueryInput::VALUE_EXPORTER_OPTION_NAME => null,
                NormStringQueryInput::VALUE_EXPORTER_OPTION_NAME => null,
            ]
        );

        $resolver->setAllowedTypes('invalid_message', ['string']);
        $resolver->setAllowedTypes('invalid_message_parameters', ['array']);
        $resolver->setAllowedTypes(StringQueryInput::FIELD_LEXER_OPTION_NAME, ['null', \Closure::class]);
        $resolver->setAllowedTypes(StringQueryInput::VALUE_EXPORTER_OPTION_NAME, ['null', 'bool', \Closure::class]);
        $resolver->setAllowedTypes(NormStringQueryInput::FIELD_LEXER_OPTION_NAME, ['null', \Closure::class]);
        $resolver->setAllowedTypes(NormStringQueryInput::VALUE_EXPORTER_OPTION_NAME, ['null', 'bool', \Closure::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'field';
    }
}
