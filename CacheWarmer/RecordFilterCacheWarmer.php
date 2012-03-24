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
