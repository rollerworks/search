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

namespace Rollerworks\Component\Search\Elasticsearch\Extension\Type;

use Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateTimeConversion;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DateTimeTypeExtension.
 */
class DateTimeTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * @var DateTimeConversion
     */
    private $conversion;

    /**
     * @param DateTimeConversion $conversion
     */
    public function __construct(DateTimeConversion $conversion)
    {
        $this->conversion = $conversion;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'elasticsearch_conversion' => $this->conversion,
            ]
        );
    }

    /**
     * Returns the name of the type being extended.
     *
     * @return string
     */
    public function getExtendedType(): string
    {
        return DateTimeType::class;
    }
}
