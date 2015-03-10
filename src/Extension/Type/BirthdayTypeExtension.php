<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type;

use Rollerworks\Component\Search\AbstractFieldTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\AgeDateConversion;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Configures the AgeConversion for Doctrine ORM.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BirthdayTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * @var AgeDateConversion
     */
    private $conversion;

    /**
     * @param AgeDateConversion $conversion
     */
    public function __construct(AgeDateConversion $conversion)
    {
        $this->conversion = $conversion;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array('doctrine_dbal_conversion' => $this->conversion)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'birthday';
    }
}
