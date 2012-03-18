<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Rollerworks\RecordFilterBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testConfig()
    {
        $config = array();

        $processor     = new Processor();
        $configuration = new Configuration();
        $config        = $processor->processConfiguration($configuration, $config);

        $this->assertEquals(array('filters_namespace'      => 'RecordFilter',
                                  'filters_directory'      => '%kernel.cache_dir%/record_filters',
                                  'generate_formatters'    => false,
                                  'generate_sqlstructs'    => false,
                                  'generate_querybuilders' => false,), $config);
    }
}
