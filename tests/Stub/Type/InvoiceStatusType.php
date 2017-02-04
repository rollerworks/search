<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type;

use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class InvoiceStatusType extends AbstractFieldType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            ['choices' => ['concept' => 0, 'publish' => 1, 'paid' => 2]]
        );
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
