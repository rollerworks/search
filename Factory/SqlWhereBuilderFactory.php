<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Factory;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\Record\Sql\WhereBuilder;
use Metadata\MetadataFactoryInterface;
use Doctrine\ORM\EntityManager;

/**
 * This factory is used to create 'Domain specific' RecordFilter QueryBuilder Classes at runtime.
 * The information is read from a FieldSet object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlWhereBuilderFactory extends AbstractFactory
{
    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * Set the default EntityManager.
     *
     * @param EntityManager $entityManager
     *
     * @api
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Set the default MetadataFactory.
     *
     * @param MetadataFactoryInterface $metadataFactory
     *
     * @api
     */
    public function setMetadataFactory(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Returns an WhereBuilder instance based on the given FieldSet.
     *
     * @param FieldSet                      $fieldSet
     * @param EntityManager|null            $entityManager
     * @param MetadataFactoryInterface|null $metadataFactory
     *
     * @return WhereBuilder
     *
     * @throws \InvalidArgumentException
     */
    public function getWhereBuilder(FieldSet $fieldSet, EntityManager $entityManager = null, MetadataFactoryInterface $metadataFactory = null)
    {
        if (null === $fieldSet->getSetName()) {
            throw new \InvalidArgumentException('FieldSet must have a unique-name.');
        }

        if (null === $entityManager) {
            $entityManager = $this->entityManager;
        }

        if (null === $entityManager) {
            throw new \InvalidArgumentException('No EntityManager set.');
        }

        if (null === $metadataFactory) {
            $metadataFactory = $this->metadataFactory;
        }

        if (null === $metadataFactory) {
            throw new \InvalidArgumentException('No MetadataFactory set.');
        }

        $fqn = $this->namespace . $fieldSet->getSetName() . '\SqlWhereBuilder';

        if (!class_exists($fqn, false)) {
            $fileName = $this->classesDir . DIRECTORY_SEPARATOR . $fieldSet->getSetName() . DIRECTORY_SEPARATOR . 'SqlWhereBuilder.php';

            if ($this->autoGenerate) {
                $this->generateClass($fieldSet->getSetName(), $fieldSet, $this->classesDir);
            }

            require $fileName;
        }

        return new $fqn($entityManager, $metadataFactory);
    }

    /**
     * Generates Classes for all FieldSets.
     *
     * @param FieldSet[] $classes FieldSets
     * @param string     $toDir   The target directory of the Classes. If not specified, the directory configured by this factory is used.
     *
     * @throws \InvalidArgumentException
     */
    public function generateClasses(array $classes, $toDir = null)
    {
        if (null === $toDir) {
            $toDir = $this->classesDir;
        }

        foreach ($classes as $fieldSet) {
            if (null === $fieldSet->getSetName()) {
                throw new \InvalidArgumentException('FieldSet must have a unique-name.');
            }

            $this->generateClass($fieldSet->getSetName(), $fieldSet, $toDir);
        }
    }

    /**
     * Generates a SqlWhereBuilder class file.
     *
     * @param string   $ns
     * @param FieldSet $fieldSet
     * @param string   $toDir
     *
     * @throws \RuntimeException
     */
    protected function generateClass($ns, FieldSet $fieldSet, $toDir)
    {
        $whereBuilder = $this->generateQueryBuilder($fieldSet);

        $placeholders = array('<namespace>', '<whereBuilder>');
        $replacements = array($this->namespace . $ns, $whereBuilder);

        $file = str_replace($placeholders, $replacements, self::$_ClassTemplate);
        $dir  = $toDir . DIRECTORY_SEPARATOR . $ns;

        if (!is_dir($dir) && !mkdir($dir)) {
            throw new \RuntimeException('Was unable to create the sub-dir for the RecordFilter::Record::Sql::WhereBuilder.');
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'SqlWhereBuilder.php', $file, LOCK_EX);
    }

    /**
     * Generates the fieldSet SqlWhereBuilder code.
     *
     * @param FieldSet $fieldSet
     *
     * @return string
     */
    protected function generateQueryBuilder(FieldSet $fieldSet)
    {
        $query = <<<'QY'
    protected function buildWhere(FormatterInterface $formatter)
    {
        $query = '';

        foreach ($formatter->getFilters() as $filters) {
            $query .= "(\n";
            $hasFields = false;
QY;

        foreach ($fieldSet->all() as $fieldName => $field) {
            if (null === $field->getPropertyRefClass()) {
                continue;
            }

            $_fieldName = var_export($fieldName, true);

            // XXX We should properly also hard-code the result of getFieldColumn()

            $query .= <<<QY


            if (isset(\$filters[$_fieldName])) {
                \$hasFields = true;
                \$valuesBag = \$filters[$_fieldName];
                \$column = \$this->getFieldColumn($_fieldName);

                if (\$valuesBag->hasSingleValues()) {
                    \$query .= sprintf('%s IN(%s) AND ', \$column, \$this->createInList(\$valuesBag->getSingleValues(), $_fieldName));
                }

                if (\$valuesBag->hasExcludes()) {
                    \$query .= sprintf('%s NOT IN(%s) AND ', \$column, \$this->createInList(\$valuesBag->getExcludes(), $_fieldName));
                }

QY;

                if ($field->acceptRanges()) {
                    $query .= <<<QY

                foreach (\$valuesBag->getRanges() as \$range) {
                    \$query .= sprintf('(%s BETWEEN %s AND %s) AND ', \$column, \$this->getValStr(\$range->getLower(), $_fieldName), \$this->getValStr(\$range->getUpper(), $_fieldName));
                }

                foreach (\$valuesBag->getExcludedRanges() as \$range) {
                    \$query .= sprintf('(%s NOT BETWEEN %s AND %s) AND ', \$column, \$this->getValStr(\$range->getLower(), $_fieldName), \$this->getValStr(\$range->getUpper(), $_fieldName));
                }

QY;
                }

                if ($field->acceptCompares()) {
                    $query .= <<<QY

                foreach (\$valuesBag->getCompares() as \$comp) {
                    \$query .= sprintf('%s %s %s AND ', \$column, \$comp->getOperator(), \$this->getValStr(\$comp->getValue(), $_fieldName));
                }

QY;
                }

            $query .= <<<'QY'
            }

QY;
        }

        $query .= <<<'QY'

            if ($hasFields) {
                $query = trim($query, " AND ") . ")\n OR ";
            } else {
                $query = rtrim($query, "(\n");
            }
        }

        $query = trim($query, " OR ");

        return $query;
    }
QY;

        return trim($query);
    }

    /** Class code template */
    private static $_ClassTemplate =
'<?php

namespace <namespace>;

use Rollerworks\Bundle\RecordFilterBundle\Record\Sql\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * THIS CLASS WAS GENERATED BY Rollerworks/RecordFilterBundle. DO NOT EDIT THIS FILE.
 */
class SqlWhereBuilder extends WhereBuilder
{
    <whereBuilder>
}
';
}
