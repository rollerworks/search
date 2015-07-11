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
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'invalid_message' => 'This value is not valid.',
                'invalid_message_parameters' => array(),
                'model_class' => null,
                'model_property' => null,
            )
        );

        // BC layer for Symfony 2.7 and 3.0
        if ($resolver instanceof OptionsResolverInterface) {
            $resolver->setAllowedTypes(
                array(
                    'invalid_message' => array('string'),
                    'invalid_message_parameters' => array('array'),
                )
            );
        } else {
            $resolver->setAllowedTypes('invalid_message', array('string'));
            $resolver->setAllowedTypes('invalid_message_parameters', array('array'));
        }
    }
}
