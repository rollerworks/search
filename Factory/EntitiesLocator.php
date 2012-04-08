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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\MappingException;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Finder\Finder;

/**
 * EntitiesLocator maps Entity-names to hashes (and vice versa) for usage in user-input.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @todo Allow truncating the hash, and increase on collision
 */
class EntitiesLocator
{
    protected $cache = array('hashes'          => array(),
                             'classes'         => array(),
                             'trimmed_hashes'  => array());

    /**
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    protected $annotationReader;

    /**
     * @var \Symfony\Component\HttpKernel\KernelInterface
     */
    protected $kernel;

    /**
     * @var array
     */
    private $entities = null;

    /**
     * Constructor.
     *
     * @param KernelInterface     $kernel
     * @param string              $cacheDir The cache path
     */
    public function __construct(KernelInterface $kernel, $cacheDir = null)
    {
        if (null !== $cacheDir && file_exists($cache = $cacheDir.'/entities_hash_mapping.php')) {
            $this->cache = require $cache;
        }

        $this->annotationReader = new AnnotationReader();
        $this->kernel           = $kernel;
    }

    /**
     * Returns a full class-name for a given hash.
     *
     * @param string $hash
     * @return string The full namespace and class-name
     *
     * @throws \InvalidArgumentException When the hash can not be found
     */
    public function hashToClass($hash)
    {
        if (count($this->cache['hashes']) < 1) {
            $this->initCache();
        }

        if (strlen($hash) > 40) {
            throw new \InvalidArgumentException('Unable to find entity hash mapping. Illegal hash given.');
        }

        if (isset($this->cache['hashes'][$hash])) {
            return $this->cache['hashes'][$hash];
        }
        elseif (isset($this->cache['trimmed_hashes'][$hash])) {
            return $this->cache['trimmed_hashes'][$hash];
        }
        else {
            throw new \InvalidArgumentException(sprintf('Unable to find entity hash mapping "%s".', $hash), 0);
        }
    }

    /**
     * Returns a class hash path for a given entity.
     *
     * @param string $class
     * @return string The entity hash
     */
    public function classToHash($class)
    {
        if (!isset($this->cache['classes'][$class])) {
            $this->cache['classes'][$class] = sha1($class);
        }

        return $this->cache['classes'][$class];
    }

    /**
     * Return all the found Entities in all the bundles.
     *
     * @return string[] Array containing all the Entity classes
     */
    public function getAllEntities()
    {
        if (null !== $this->entities) {
            return $this->entities;
        }

        $entities = array();

        foreach ($this->kernel->getBundles() as $bundle) {
            $entities = array_merge($entities, $this->findEntitiesInBundle($bundle));
        }

        return $this->entities = $this->getAllClassNames($entities);
    }

    /**
     * Return all the found hashes and the corresponding the Entity class
     *
     * Trimmed hashes are at minimum 11 characters, and bigger when they are not unique
     *
     * @param boolean $trimmed Return the trimmed hashes
     * @return Array containing all hashes and the corresponding the Entity class
     */
    public function getAllHashes($trimmed = false)
    {
        if (count($this->cache['hashes']) < 1) {
            $this->initCache();
        }

        if ($trimmed) {
            return $this->cache['trimmed_hashes'];
        }
        else {
            return $this->cache['hashes'];
        }
    }

    /**
     * Initialize the hashes-cache.
     *
     * Search all entities and create hashes from the found-names.
     */
    protected function initCache()
    {
        foreach ($this->getAllEntities() as $entityName) {
            $hash = sha1($entityName);

            $this->cache['hashes'][$hash]                              = $entityName;
            $this->cache['classes'][$entityName]                       = $hash;
            $this->cache['trimmed_hashes'][$this->getUniqueHash($hash)] = $entityName;
        }
    }

    /**
     * Return a unique trimmed hash.
     *
     * @param string $hash
     * @return string
     */
    protected function getUniqueHash($hash)
    {
        $trimmedHash = substr($hash, 0, 11);

        if (isset($this->cache['trimmed_hashes'][$trimmedHash])) {
            for ($i = 12; $ $i <= 40; $i++) {
                $trimmedHash = substr($hash, 0, $i);

                if (!isset($this->cache['trimmed_hashes'][$trimmedHash])) {
                    break;
                }
            }
        }

        return $trimmedHash;
    }

    /**
     * Find templates in the given directory.
     *
     * @param string $dir The folder where to look for templates
     * @return array
     */
    private function findEntitiesInFolder($dir)
    {
        $entities = array();

        if (is_dir($dir)) {
            $finder = new Finder();

            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            foreach ($finder->files()->followLinks()->in($dir) as $file) {
                $entities[] = $file->getPath();
            }
        }

        return $entities;
    }

    /**
     * Find Entities in the given bundle.
     *
     * @param \Symfony\Component\HttpKernel\Bundle\BundleInterface $bundle The bundle where to look for templates
     * @return array
     */
    private function findEntitiesInBundle(BundleInterface $bundle)
    {
        return $this->findEntitiesInFolder($bundle->getPath() . '/Entity');
    }

    /**
     * Gets the names of all mapped classes.
     *
     * @see \Doctrine\ORM\Mapping\AbstractFileDriver#getAllClassNames
     *
     * @param array $paths
     * @return array The names of all mapped classes.
     */
    private function getAllClassNames($paths)
    {
        $classes       = array();
        $includedFiles = array();

        foreach ($paths as $path) {
            if (!is_dir($path)) {
                throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
            }

            $iterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path),
                \RecursiveIteratorIterator::LEAVES_ONLY), '/^.+\\.php$/i', \RecursiveRegexIterator::GET_MATCH);

            foreach ($iterator as $file) {
                $sourceFile = realpath($file[0]);

                if (in_array($sourceFile, $includedFiles)) {
                    continue;
                }

                if (in_array($sourceFile, get_included_files())) {
                    $includedFiles[] = $sourceFile;
                    continue;
                }

                require $sourceFile;

                $includedFiles[] = $sourceFile;
            }
        }

        $declared = get_declared_classes();

        foreach ($declared as $className) {
            $rc         = new \ReflectionClass($className);
            $sourceFile = $rc->getFileName();

            if (in_array($sourceFile, $includedFiles) && $this->isTransient($rc)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }

    /**
     * Checks if the class contains the \Rollerworks\RecordFilterBundle\Annotation\Field Annotation
     *
     * @see \Doctrine\ORM\Mapping\Driver#isTransient
     *
     * @param \ReflectionClass $classAnnotations
     * @return boolean
     */
    private function isTransient($classAnnotations)
    {
        $classAnnotations = $this->annotationReader->getClassAnnotations($classAnnotations);

        // Compatibility with Doctrine Common 3.x
        if ($classAnnotations && is_int(key($classAnnotations))) {
            foreach ($classAnnotations as $annot) {
                if ($annot instanceof \Rollerworks\RecordFilterBundle\Annotation\Field) {
                    return true;
                }
            }

            return false;
        }

        return isset($classAnnotations['Rollerworks\\RecordFilterBundle\\Annotation\\Field']);
    }
}
