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

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Type;

use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\ChildCountType as DbalChildCountType;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion\ChildCountConversion;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChildCountType extends AbstractFieldTypeExtension
{
    private $conversion;

    public function __construct()
    {
        $this->conversion = new ChildCountConversion();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('doctrine_orm_conversion', $this->conversion);
    }

    public function getExtendedType(): string
    {
        return DbalChildCountType::class;
    }
}
