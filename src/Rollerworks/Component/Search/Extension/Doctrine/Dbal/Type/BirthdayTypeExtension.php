<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type;

use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\AgeDateConversion;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldTypeExtensionInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Configures the AgeConversion for Doctrine ORM.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BirthdayTypeExtension implements FieldTypeExtensionInterface
{
    /**
     * @var AgeDateConversion
     */
    private $conversion;

    /**
     * @param AgeDateConversion $conversion
     */
    public function __construct($conversion)
    {
        $this->conversion = $conversion;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'doctrine_dbal_conversion' => $this->conversion
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'birthday';
    }
}
