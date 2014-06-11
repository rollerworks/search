<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @param boolean          $test  Don't use this parameter, its only used for testing
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
     * {@inheritDoc}
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
     * @param boolean          $test
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
