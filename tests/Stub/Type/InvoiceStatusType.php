<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\Doctrine\Conversion\InvoiceLabelConverter;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceStatusType extends AbstractFieldType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array('choices' => array(0 => 'concept', 1 => 'publish', 2 => 'paid'))
        );
    }

    public function getName()
    {
        return 'invoice_status';
    }

    public function getParent()
    {
        return 'choice';
    }
}
