<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
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

namespace Rollerworks\RecordFilterBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;

/**
 * Records filter configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class RollerworksRecordFilterExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('record_filter.xml');

        $container->setParameter('rollerworks_record_filter.filters_directory', $config['filters_directory']);
        $container->setParameter('rollerworks_record_filter.filters_namespace', $config['filters_namespace']);

        $factoriesList = array('formatter', 'sqlstruct', 'querybuilder');

        foreach ($factoriesList as $factoryName) {
            $container->setParameter(sprintf('rollerworks_record_filter.%s_factory.auto_generate', $factoryName), $config['generate_'.$factoryName.'s']);
        }
    }
}

