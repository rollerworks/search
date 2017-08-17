<?php

declare(strict_types=1);

namespace Rollerworks\Bundle\SearchBundle\Tests\Fixtures\FieldSet;

use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\FieldSetConfigurator;

final class UserFieldSet implements FieldSetConfigurator
{
    public function buildFieldSet(FieldSetBuilder $builder): void
    {
        $builder->add('id', IntegerType::class);
        $builder->add('name', TextType::class);
    }
}
