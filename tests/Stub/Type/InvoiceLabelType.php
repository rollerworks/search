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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type;

use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\Doctrine\Conversion\InvoiceLabelConverter;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceLabelType extends AbstractFieldType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['doctrine_dbal_conversion' => new InvoiceLabelConverter()]
        );
    }

    public function getName()
    {
        return 'invoice_label';
    }
}
