<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata\Driver;

use Metadata\Driver\AdvancedDriverInterface;
use Metadata\Driver\AdvancedFileLocatorInterface;

/**
 * Base file driver implementation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractFileDriver implements AdvancedDriverInterface
{
    /**
     * @var AdvancedFileLocatorInterface
     */
    private $locator;

    /**
     * @param AdvancedFileLocatorInterface $locator
     */
    public function __construct(AdvancedFileLocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @param \ReflectionClass $class
     * @param bool             $test  Don't use this parameter, its only used for testing
     *
     * @return \Metadata\ClassMetadata
     */
    public function loadMetadataForClass(\ReflectionClass $class, $test = false)
    {
        if (null === $path = $this->locator->findFileForClass($class, $this->getExtension())) {
            return null;
        }

        $classMetadata = $this->loadMetadataFromFile($class, $path, $test);

        if ($test) {
            $classMetadata->reflection = null;
            $classMetadata->createdAt = null;
        }

        return $classMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        return $this->locator->findAllClasses($this->getExtension());
    }

    /**
     * Parses the content of the file, and converts it to the desired metadata.
     *
     * @param \ReflectionClass $class
     * @param string           $file
     * @param bool             $test
     *
     * @return \MetaData\ClassMetadata|null
     */
    abstract protected function loadMetadataFromFile(\ReflectionClass $class, $file, $test = false);

    /**
     * Returns the extension of the file.
     *
     * @return string
     */
    abstract protected function getExtension();
}
