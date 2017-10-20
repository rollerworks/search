<?php

declare(strict_types=1);

namespace Rollerworks\Component\Search\Elasticsearch\Extension\Type;

use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
use Rollerworks\Component\Search\Field\AbstractFieldTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FieldTypeExtension.
 */
class FieldTypeExtension extends AbstractFieldTypeExtension
{
    /**
     * {@inheritdoc}
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'elasticsearch_conversion' => null,
                'elasticsearch_convert_to_range' => false
            ]
        );
        $resolver->setAllowedTypes(
            'elasticsearch_conversion',
            [
                'null',
                ValueConversion::class,
            ]
        );
    }
    /**
     * {@inheritdoc}
     */
    public function getExtendedType(): string
    {
        return SearchFieldType::class;
    }
}
