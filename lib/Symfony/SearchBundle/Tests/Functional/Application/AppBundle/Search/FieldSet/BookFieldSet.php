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

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppBundle\Search\FieldSet;

use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\FieldSetConfigurator;

final class BookFieldSet implements FieldSetConfigurator
{
    public function buildFieldSet(FieldSetBuilder $builder): void
    {
        $builder->add('id', IntegerType::class);
        $builder->add('title', TextType::class);
    }
}
