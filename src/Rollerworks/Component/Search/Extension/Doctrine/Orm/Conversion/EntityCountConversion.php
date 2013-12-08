<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion;

use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;

/**
 * EntityCountConversion.
 *
 * Allows counting the number of parent/children references.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class EntityCountConversion implements SqlFieldConversionInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertSqlField($column, array $options, array $hints)
    {
        /* @var \Doctrine\ORM\EntityManager $em */
        $em = $hints['entityManager'];
        /** @var FieldConfigInterface $field */
        $field = $hints['searchField'];

        // Note we do the table/field -name resolving here and not in the type
        // The options require the EntityManager which is not available until
        // all options are resolved

        if (null === $options['table_name']) {
            $refClassMeta = $em->getClassMetadata($em->getClassMetadata($field->getModelRefClass())
                ->getAssociationTargetClass($field->getModelRefProperty()));

            $options['table_name'] = $refClassMeta->getTableName();
        }

        if (null === $options['table_field']) {
            $classMetadata = $em->getClassMetadata($field->getModelRefClass());
            $options['table_field'] = $classMetadata->getFieldForColumn($field->getModelRefProperty());
        }

        return "(SELECT COUNT(*) FROM " . $options['table_name'] . " WHERE " . $options['table_field'] . " = $column)";
    }
}
