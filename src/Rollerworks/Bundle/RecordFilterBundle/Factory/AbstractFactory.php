<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This abstract factory provides the basics to create the RecordFilter::* Classes at runtime.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractFactory
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Whether to automatically (re)generate the classes.
     *
     * @var boolean
     */
    protected $autoGenerate;

    /**
     * The namespace that contains all the classes.
     *
     * @var string
     */
    protected $namespace;

    /**
     * The directory that contains all the classes.
     *
     * @var string
     */
    protected $classesDir;

    /**
     * Constructor.
     *
     * @param string  $classesDir   The directory to use for the classes. It must exist.
     * @param string  $filtersNs    The namespace to use for the classes.
     * @param boolean $autoGenerate Whether to automatically generate the classes.
     *
     * @api
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($classesDir, $filtersNs, $autoGenerate = false)
    {
        if (!$classesDir) {
            throw new \InvalidArgumentException('You must configure a filters Formatter directory. See the docs for details');
        }

        if (!$filtersNs || !self::isNamespace($filtersNs)) {
            throw new \InvalidArgumentException('You must configure a _valid_ filters namespace. See the docs for details');
        }

        $this->autoGenerate = $autoGenerate;
        $this->classesDir   = $classesDir;
        $this->namespace    = trim($filtersNs, '\\') . '\\';
    }

    /**
     * Set the DIC container.
     *
     * @param ContainerInterface $container
     *
     * @api
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param string $input
     *
     * @return boolean
     */
    protected static function isNamespace($input)
    {
        return preg_match('#^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$#s', $input) ? true : false;
    }
}
