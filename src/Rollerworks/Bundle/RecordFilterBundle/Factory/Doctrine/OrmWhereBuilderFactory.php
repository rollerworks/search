<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Factory\Doctrine;

use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Factory\AbstractFactory;
use Metadata\MetadataFactoryInterface;
use Doctrine\ORM\EntityManager;

/**
 * This factory is used to create 'Domain specific' RecordFilter
 * Doctrine ORM WhereBuilder Classes at runtime.
 *
 * The information is read from a FieldSet object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class OrmWhereBuilderFactory extends AbstractFactory
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
     * Returns a new WhereBuilder instance based on the given FieldSet.
     *
     * When there is no class (yet),
     * its generated unless auto generation is disabled.
     *
     * @param FieldSet                      $fieldSet
     * @param EntityManager|null            $entityManager
     * @param MetadataFactoryInterface|null $metadataFactory
     *
     * @return WhereBuilder
     *
     * @throws \InvalidArgumentException When missing required information
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

        $fqn = $this->namespace . $fieldSet->getSetName() . '\DoctrineOrmWhereBuilder';

        if (!class_exists($fqn, false)) {
            $fileName = $this->classesDir . DIRECTORY_SEPARATOR . $fieldSet->getSetName() . DIRECTORY_SEPARATOR . 'DoctrineOrmWhereBuilder.php';

            if ($this->autoGenerate) {
                $this->generateClass($fieldSet->getSetName(), $fieldSet, $this->classesDir);
            }

            require $fileName;
        }

        $whereBuilder = new $fqn($metadataFactory, $this->container, $entityManager);

        return $whereBuilder;
    }

    /**
     * Generates Classes for all the given FieldSets.
     *
     * @param FieldSet[] $classes An array of FieldSet objects. The Fields must contain an property reference
     * @param string     $toDir   The target directory of the Classes. If not specified, the directory configured by this factory is used
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
                throw new \InvalidArgumentException('FieldSet name can not be null, and must be unique.');
            }

            $this->generateClass($fieldSet->getSetName(), $fieldSet, $toDir);
        }
    }

    /**
     * Generates an DoctrineOrmWhereBuilder class file.
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

        $file = str_replace($placeholders, $replacements, self::$classTemplate);
        $dir  = $toDir . DIRECTORY_SEPARATOR . $ns;

        if (!is_dir($dir) && !mkdir($dir)) {
            throw new \RuntimeException('Was unable to create the sub-dir for RecordFilter::Doctrine::Orm::WhereBuilder.');
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'DoctrineOrmWhereBuilder.php', $file, LOCK_EX);
    }

    /**
     * Generates the DoctrineOrmWhereBuilder code based on the given FieldSet.
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
QY;

        $query .= <<<QY

        if ('{$fieldSet->getSetName()}' !== \$formatter->getFieldSet()->getSetName()) {
            throw new \LogicException(sprintf('Expected FieldSet "{$fieldSet->getSetName()}" but got "%s" instead.', \$formatter->getFieldSet()->getSetName()));
        }
QY;

        $query .= <<<'QY'

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

            $query .= <<<QY

            if (isset(\$filters[$_fieldName])) {
                \$hasFields = true;
                \$valuesBag = \$filters[$_fieldName];
                \$field = \$this->fieldSet->get($_fieldName);
                \$this->initFilterField($_fieldName, \$field);
                \$column = (\$this->valueConversions[$_fieldName][0] instanceof ConversionStrategyInterface ? '' : \$this->getFieldColumn($_fieldName, \$field));
                \$valuesGroup = '';

                if (\$valuesBag->hasSingleValues()) {
                    \$valuesGroup .= \$this->processSingleValues(\$valuesBag->getSingleValues(), \$column, $_fieldName, \$field);
                }

QY;

                if ($field->acceptRanges()) {
                    $query .= <<<QY

                if (\$valuesBag->hasRanges()) {
                    \$valuesGroup .= \$this->processRanges(\$valuesBag->getRanges(), \$column, $_fieldName, \$field);
                }

QY;
                }

                if ($field->acceptCompares()) {
                    $query .= <<<QY

                if (\$valuesBag->hasCompares()) {
                    \$valuesGroup .= \$this->processCompares(\$valuesBag->getCompares(), \$column, $_fieldName, \$field);
                }

QY;
                }

            $query .= <<<'QY'
                if (!empty($valuesGroup)) {
                    $valuesGroup = rtrim($valuesGroup, " OR ");

                    if ($valuesBag->hasExcludes() || $valuesBag->hasExcludedRanges()) {
                        $query .= "($valuesGroup)\n AND\n ";
                    } else {
                        $query .= $valuesGroup;
                    }
                }

QY;

            $query .= <<<QY
                if (\$valuesBag->hasExcludes()) {
                    \$query .= \$this->processSingleValues(\$valuesBag->getExcludes(), \$column, $_fieldName, \$field, true);
                }

QY;

                if ($field->acceptRanges()) {
                    $query .= <<<QY

                if (\$valuesBag->hasExcludedRanges()) {
                    \$query .= \$this->processRanges(\$valuesBag->getExcludedRanges(), \$column, $_fieldName, \$field, true);
                }

QY;
                }

            $query .= <<<'QY'
                $query .= " AND ";

            }
QY;
        }

        $query .= <<<'QY'

            if ($hasFields) {
                $query = rtrim($query, " AND ") . ")\n OR ";
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
    private static $classTemplate =
'<?php

namespace <namespace>;

use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * THIS CLASS WAS GENERATED BY Rollerworks/RecordFilterBundle. DO NOT EDIT THIS FILE.
 */
class DoctrineOrmWhereBuilder extends WhereBuilder
{
    <whereBuilder>
}
';
}
