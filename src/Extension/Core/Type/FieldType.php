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
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldType extends AbstractFieldType
{
    /**
     * @var ValueComparisonInterface
     */
    private $valueComparison;

    /**
     * Constructor.
     *
     * @param ValueComparisonInterface $valueComparison
     */
    public function __construct(ValueComparisonInterface $valueComparison)
    {
        $this->valueComparison = $valueComparison;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'field';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        // noop
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);

        if (null !== $options['model_mappings'] && ($options['model_class'] || $options['model_property'])) {
            throw new InvalidConfigurationException(
                sprintf(
                    'Option "model_mappings" cannot be set in combination with "model_class" '.
                    'and/or "model_property" for field "%s"',
                    $config->getName()
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => [],
                'model_class' => null,
                'model_property' => null,
                'model_mappings' => null,
            ]
        );

        $resolver->setAllowedTypes('invalid_message', ['string']);
        $resolver->setAllowedTypes('invalid_message_parameters', ['array']);
        $resolver->setAllowedTypes('model_mappings', ['array', 'null']);
    }
}
