<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Extension\Core\ChoiceList\SimpleChoiceList;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToLabelTransformer;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\ChoiceToValueTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChoiceType extends AbstractFieldType
{
    /**
     * Caches created choice lists.
     *
     * @var array
     */
    private $choiceListCache = array();

    /**
     * {@inheritDoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        if (!$options['choice_list'] && !is_array($options['choices']) && !$options['choices'] instanceof \Traversable) {
            throw new InvalidConfigurationException('Either the option "choices" or "choice_list" must be set.');
        }

        if ($options['label_as_value']) {
            $config->addViewTransformer(new ChoiceToLabelTransformer($options['choice_list']));
        } else {
            $config->addViewTransformer(new ChoiceToValueTransformer($options['choice_list']));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choiceListCache = &$this->choiceListCache;

        $choiceList = function (Options $options) use (&$choiceListCache) {
            // Harden against NULL values (like in EntityType and ModelType)
            $choices = null !== $options['choices'] ? $options['choices'] : array();

            // Reuse existing choice lists in order to increase performance
            $hash = hash('sha256', json_encode(array($choices)));

            if (!isset($choiceListCache[$hash])) {
                $choiceListCache[$hash] = new SimpleChoiceList($choices);
            }

            return $choiceListCache[$hash];
        };

        $resolver->setDefaults(array(
            'label_as_value' => false,
            'choice_list' => $choiceList,
            'choices' => array(),
        ));

        $resolver->setAllowedTypes(array(
            'choice_list' => array('null', 'Rollerworks\Component\Search\Extension\Core\ChoiceList\ChoiceListInterface'),
        ));
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName()
    {
        return 'choice';
    }
}
