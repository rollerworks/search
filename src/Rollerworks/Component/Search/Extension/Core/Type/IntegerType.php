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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IntegerType extends AbstractFieldType
{
    /**
     * @var ValueComparisonInterface
     */
    protected $valueComparison;

    public function __construct(ValueComparisonInterface $valueComparison)
    {
        $this->valueComparison = $valueComparison;
    }

    /**
     * {@inheritDoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        $config->setValueComparison($this->valueComparison);

        $config->addViewTransformer(
            new IntegerToLocalizedStringTransformer(
                $options['precision'],
                $options['grouping'],
                $options['rounding_mode']
        ));
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            // default precision is locale specific (usually around 3)
            'precision' => null,
            'grouping' => false,
            // Integer cast rounds towards 0, so do the same when displaying fractions
            'rounding_mode' => \NumberFormatter::ROUND_DOWN,
        ));

        $resolver->setAllowedValues(array(
            'rounding_mode' => array(
                \NumberFormatter::ROUND_FLOOR,
                \NumberFormatter::ROUND_DOWN,
                \NumberFormatter::ROUND_HALFDOWN,
                \NumberFormatter::ROUND_HALFEVEN,
                \NumberFormatter::ROUND_HALFUP,
                \NumberFormatter::ROUND_UP,
                \NumberFormatter::ROUND_CEILING,
            ),
        ));
    }



    /**
     * {@inheritDoc}
     */
    public function hasRangeSupport()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function hasCompareSupport()
    {
        return true;
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName()
    {
        return 'integer';
    }
}
