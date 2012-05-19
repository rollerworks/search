<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Annotations\Reader;

/**
 * This abstract factory annotations provides the basics to create the RecordFilter::* Classes at runtime.
 * The information is read from the Annotations of the Class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractFactory
{
    /**
     * The EntityManager this factory is bound to.
     *
     * @var \Doctrine\Common\Annotations\Reader
     */
    protected $annotationReader;

    /**
     * DIC container
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * Whether to automatically (re)generate te Classes.
     *
     * @var boolean
     */
    protected $autoGenerate;

    /**
     * The namespace that contains all te Classes.
     *
     * @var string
     */
    protected $namespace;

    /**
     * The directory that contains all te Classes.
     *
     * @var string
     */
    protected $classesDir;

    /**
     * Constructor
     *
     * @param Reader  $annotationReader
     * @param string  $classesDir       The directory to use for the Classes. It must exist.
     * @param string  $filtersNs        The namespace to use for the Classes.
     * @param boolean $autoGenerate     Whether to automatically generate Classes.
     *
     * @api
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(Reader $annotationReader, $classesDir, $filtersNs, $autoGenerate = false)
    {
        if (!$classesDir) {
            throw new \InvalidArgumentException('You must configure a filters Formatter directory. See docs for details');
        }

        if (!$filtersNs) {
            throw new \InvalidArgumentException('You must configure a filters Formatter namespace. See docs for details');
        }
        elseif (!self::isNamespace($filtersNs)) {
            throw new \InvalidArgumentException('You must configure a _valid_ filters Formatter namespace. See docs for details');
        }

        $this->autoGenerate     = $autoGenerate;
        $this->annotationReader = $annotationReader;
        $this->classesDir       = $classesDir;
        $this->namespace        = trim($filtersNs, '\\') . '\\';
    }

    /**
     * Set the DIC container for types that need it
     *
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     *
     * @api
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Generates Classes for all given Entity's.
     *
     * @param string[] $classes The Entity Classes names
     * @param string   $toDir   The target directory of the Formatter Classes. If not specified, the directory configured by this factory is used.
     *
     * @api
     */
    public function generateClasses(array $classes, $toDir = null)
    {
        $toDir = $toDir ?: $this->classesDir;
        $toDir = rtrim($toDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach ($classes as $class) {
            $sEntityNS = str_replace('\\', '', $class);

            $this->generateClass($class, $sEntityNS, $this->annotationReader->getClassAnnotations(new \ReflectionClass($class)), $toDir);
        }
    }

    /**
     * @param string $input
     * @return boolean
     */
    static protected function isNamespace($input)
    {
        return preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$#s', $input) ? true : false;
    }

    /**
     * Get namespace of the formatter.
     *
     * @param string $class
     * @param string $formatterClass
     * @return string
     * @throws \UnexpectedValueException When the formatter is not domain specific.
     */
    protected function getFormatterNS($class, $formatterClass = 'Formatter')
    {
        $nameLength = strlen($formatterClass) + 1;
        $Ns         = mb_substr($class, strlen($this->namespace), -$nameLength);

        if ('\\' . $formatterClass !== mb_substr($class, -$nameLength, $nameLength)) {
            throw new \UnexpectedValueException('This Formatter does not appear to be made by a ' . $formatterClass . ' Factory.');
        }

        return str_replace('\\', '', $Ns);
    }

    /**
     * Generates a annotations file.
     *
     * @param string $class
     * @param string $entityCompactNS
     * @param object $annotations
     * @param string $toDir
     */
    protected abstract function generateClass($class, $entityCompactNS, $annotations, $toDir);

    /**
     * Build the constructor parameters list.
     * Returns the parameters as string (for direct PHP usage)
     *
     * @param \ReflectionMethod $methodReflection Constructor reflection
     * @param array             $params
     * @param string            $type
     * @param array             $noConvert
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function compileConstructorParams(\ReflectionMethod $methodReflection, $params, $type, $noConvert = array())
    {
        if (!$methodReflection->isPublic()) {
            return '';
        }

        $paramsList = '';

        /** @var \ReflectionParameter $parameter */
        foreach ($methodReflection->getParameters() as $parameter) {
            if (isset($params[ $parameter->name ])) {
                if (in_array($parameter, $noConvert)) {
                    $paramsList .= $params[ $parameter->name ];
                }
                else {
                    $paramsList .= var_export($params[ $parameter->name ], true);
                }
            }
            elseif (!$parameter->isOptional()) {
                throw new \InvalidArgumentException('Constructor parameter "' . $parameter->name . '" is not optional and should have value in annotations: ' . $type);
            }
            else {
                $paramsList .= var_export($parameter->getDefaultValue(), true);
            }

            $paramsList .= ', ';
        }

        return rtrim($paramsList, ', ');
    }
}
