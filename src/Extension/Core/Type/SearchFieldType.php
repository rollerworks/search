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
use Rollerworks\Component\Search\ValueComparator;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchFieldType extends AbstractFieldType
{
    /**
     * @var ValueComparator
     */
    private $valueComparison;

    /**
     * Constructor.
     *
     * @param ValueComparator $valueComparison
     */
    public function __construct(ValueComparator $valueComparison)
    {
        $this->valueComparison = $valueComparison;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        // no-op
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfig $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'translation_domain' => 'messages',
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
            ]
        );

        $resolver->setAllowedTypes('invalid_message', ['string']);
        $resolver->setAllowedTypes('invalid_message_parameters', ['array']);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix(): string
    {
        return 'field';
    }
}
