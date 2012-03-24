<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Factory;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Doctrine\ORM\EntityManager;

/**
 * This factory is used to create 'Domain specific' RecordFilter QueryBuilder Classes at runtime.
 * The information is read from the Annotations of the Class.
 *
 * IMPORTANT: The Namespace must be the same as the one used with FormatterFactory
 *
 * The intent of this approach is to provide an interface that is Domain specific.
 * So its safe to assume that the 'correct' filtering configuration is used.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
class QueryBuilderFactory extends AbstractSQLFactory
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager;

    protected $annotation = '\\Rollerworks\\RecordFilterBundle\\Annotation\\QueryBuilder';

    /**
     * Set the default EntityManager.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     *
     * @api
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Gets a reference instance for the filter QueryBuilder.
     * The correct annotations is searched by the information of $formatter
     *
     * $formatter must be an reference to the 'domain specific' Formatter annotations.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface    $formatter
     * @param \Doctrine\ORM\EntityManager                                     $entityManager
     * @return \Rollerworks\RecordFilterBundle\Record\SQL\QueryBuilder
     *
     * @api
     */
    public function getQueryBuilder(FormatterInterface $formatter, EntityManager $entityManager = null)
    {
        if (empty($entityManager) && empty($this->entityManager)) {
            throw new \RuntimeException('No EntityManager configured/given.');
        }
        elseif (empty($entityManager)) {
            $entityManager = $this->entityManager;
        }

        $class = get_class($formatter);

        $entityNs = $this->getFormatterNS($class);
        $FQN      = $this->namespace . $entityNs . '\QueryBuild';

        if (!class_exists($FQN, false)) {
            $fileName = $this->classesDir . DIRECTORY_SEPARATOR . $entityNs . DIRECTORY_SEPARATOR . 'QueryBuilder.php';

            if ($this->autoGenerate) {
                $this->generateClass($formatter->BC, $entityNs, $this->annotationReader->getClassAnnotations(new \ReflectionClass($class)), $this->classesDir);
            }

            require $fileName;
        }

        return new $FQN($formatter, $entityManager);
    }


    /**
     * Generates a annotations file.
     *
     * @param string $class
     * @param string $entityCompactNS
     * @param object $annotations
     * @param string $toDir
     */
    protected function generateClass($class, $entityCompactNS, $annotations, $toDir)
    {
        /*
        $oQueryBuilderAnnotation = $this->oAnnotationReader->getClassAnnotation(new \ReflectionClass($class), $this->_sAnnotation);

        /** @var \Rollerworks\RecordFilterBundle\Annotation\QueryBuilder $oQueryBuilderAnnotation * /

        if (null === $oQueryBuilderAnnotation || false === $oQueryBuilderAnnotation->isEnabled()) {
            return;
        }
        */

        $whereBuilder = $this->generateQuery($annotations);

        $placeholders = array('<namespace>', '<whereBuilder>');
        $replacements = array($this->namespace . $entityCompactNS, $whereBuilder);

        $file = str_replace($placeholders, $replacements, self::$_ClassTemplate);
        $dir  = $toDir . DIRECTORY_SEPARATOR . $entityCompactNS;

        if (!is_dir($dir) && !mkdir($dir)) {
            throw new \RuntimeException('Was unable to create the Entity sub-dir for the RecordFilter::Record::SQL::QueryBuilder.');
        }

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'QueryBuilder.php', $file, LOCK_EX);
    }

    /** Class code template */
    private static $_ClassTemplate =
'<?php

namespace <namespace>;

use Rollerworks\RecordFilterBundle\Record\SQL\QueryBuilder as SQLQueryBuilder;
use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Doctrine\ORM\EntityManager;

/**
 * THIS CLASS WAS GENERATED BY Rollerworks::Framework::RecordFilter. DO NOT EDIT THIS FILE.
 */
class QueryBuilder extends SQLQueryBuilder
{
    public function __construct(FormatterInterface $formatter, $entityManager)
    {
        parent::__construct($formatter, $entityManager);
    }

    <whereBuilder>
}';
}
