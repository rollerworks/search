<?php

declare(strict_types=1);

namespace Rollerworks\Component\Search\Processor\Tests\Fixtures\FieldSet;

use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSetBuilder;
use Rollerworks\Component\Search\FieldSetConfigurator;

final class UserFieldSet implements FieldSetConfigurator
{
    /**
     * Configure the FieldSet builder.
     *
     * @param FieldSetBuilder $builder
     *
     * @return void
     */
    public function buildFieldSet(FieldSetBuilder $builder)
    {
        $builder->add('id', IntegerType::class);
        $builder->add('name', TextType::class);
    }
}
