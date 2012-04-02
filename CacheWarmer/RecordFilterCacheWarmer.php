<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\CacheWarmer;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmer;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Finder\Finder;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\MappingException;

use Rollerworks\RecordFilterBundle\Factory\EntitiesLocator;

/**
 * Generates the Classes for all RecordFilters.
 *
 * The classes generated are depended on the configuration OF application.
 * By default nothing is compiled.
 *
 * Some parts are copied from {@see Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinder}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RecordFilterCacheWarmer extends CacheWarmer
{
    protected $container;

    protected $kernel;

    /**
     * Constructor.
     *
     * @param \Symfony\Component\HttpKernel\KernelInterface             $kernel
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container The dependency injection poContainer
     */
    public function __construct(KernelInterface $kernel, ContainerInterface $container)
    {
        $this->container = $container;
        $this->kernel    = $kernel;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        if (!strlen($this->container->getParameter('rollerworks_record_filter.filters_directory'))) {
            throw new \InvalidArgumentException('You must configure a filters RecordFilter directory (when record_filter is activated in the services file). See docs for details');
        }

        // we need the directory no matter the proxy cache generation strategy
        if (!file_exists($sFilterDirectory = $this->container->getParameter('rollerworks_record_filter.filters_directory'))) {
            if (false === @mkdir($sFilterDirectory, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the RecordFilters directory "%s".', $sFilterDirectory));
            }
        }
        elseif (!is_writable($sFilterDirectory)) {
            throw new \RuntimeException(sprintf('The RecordFilters directory "%s" is not writable for the current system user.', $sFilterDirectory));
        }

        $entitiesLocator = new EntitiesLocator($this->kernel, $cacheDir);

        if ($this->container->getParameter('rollerworks_record_filter.formatter_factory.auto_generate')) {
            $this->container->get('rollerworks_record_filter.formatter_factory')->generateClasses($entitiesLocator->getAllEntities());

            if ($this->container->getParameter('rollerworks_record_filter.sqlstruct_factory.auto_generate')) {
                $this->container->get('rollerworks_record_filter.sqlstruct_factory')->generateClasses($entitiesLocator->getAllEntities());
            }

            if ($this->container->getParameter('rollerworks_record_filter.querybuilder_factory.auto_generate')) {
                $this->container->get('rollerworks_record_filter.querybuilder_factory')->generateClasses($entitiesLocator->getAllEntities());
            }
        }
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * @return boolean always true
     */
    public function isOptional()
    {
        return true;
    }
}
