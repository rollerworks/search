<?php

/**
 * This file is part of the RollerFramework package.
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

namespace Rollerworks\RecordFilterBundle\Tests\Factory;

use Rollerworks\RecordFilterBundle\Factory\EntitiesLocator;

use Rollerworks\RecordFilterBundle\Tests\TestCase;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\BaseBundle;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\TestBundle\TestBundle;

class EntitiesLocatorTest extends TestCase
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $container;

    /**
     * @var \Rollerworks\RecordFilterBundle\Factory\EntitiesLocator
     */
    protected $entitiesLocator;

    protected function setUp()
    {
        parent::setUp();

        $kernel = $this
            ->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $kernel
            ->expects($this->any())
            ->method('getBundle')
        ;

        $kernel
            ->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue(array(
                'BaseBundle' => new BaseBundle(),
                'TestBundle' => new TestBundle()
            )))
        ;

        /* * @var \Symfony\Component\HttpKernel\KernelInterface $kernel */
        $this->container = $this->createContainer();

        if (file_exists($this->container->getParameter('kernel.cache_dir') . '/entities_hash_mapping.php')) {
            //unlink($this->container->getParameter('kernel.cache_dir') . '/entities_hash_mapping.php');
        }

        $this->entitiesLocator = new EntitiesLocator($kernel, $this->container->getParameter('kernel.cache_dir'));
    }

    public function testGetAllEntities()
    {
        $expectedEntities = array (
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceInvoice',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductRange',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductReq',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductSimple',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductTwo',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductType',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType2',

            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceInvoice',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductRange',
        );

        $this->assertEquals($expectedEntities, $this->entitiesLocator->getAllEntities());

        // They are cached now
        $this->assertEquals($expectedEntities, $this->entitiesLocator->getAllEntities());
    }

    public function testHashToClass()
    {
        $expectedHashes = array (
            'c3309d0b57f9c6fb4e7c57c21e681598803de044' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '858f8392a8786ef1c32c67b941cfbd1e208400a4' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            '31272d519abbc9010b05fa616dab1e2f1aff8ed3' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductRange',
            '7279fff89b515d10e6be87bfa9d435380d136974' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductReq',
            '01fef27732d3138044e188981d276abd68a3daff' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductSimple',
            '81fbde32d14219b68b3dab0a26395fb34b11f2e0' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductTwo',
            '09568eea900e3c3dcaf128839c4ccd5bbacc5e8d' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductType',
            'c0a246ed300be6a7cedef117ec4cdccdeccec3d2' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType',
            '2f19645f9a1e380831cbd113469e60864cd97a33' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType2',
            '983ee01b5ee5bd46c629b448ee41db75c0726ace' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '95bfdbcfdbb01de83e53e265f8bf40dd30c00da5' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            'd3a4a3ac9f09bf55dd6e05b676a907324cc3f4da' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductRange',
        );

        foreach ($expectedHashes as $hash => $class) {
            $this->assertEquals($this->entitiesLocator->hashToClass($hash), $class);

            $this->assertEquals($this->entitiesLocator->hashToClass(substr($hash, 0, 11)), $class);
        }

        foreach ($expectedHashes as $hash => $class) {
            $this->assertEquals($this->entitiesLocator->hashToClass($hash), $class);

            $this->assertEquals($this->entitiesLocator->hashToClass(substr($hash, 0, 11)), $class);
        }
    }

    public function testClassToHash()
    {
        $hashes = array (
            'c3309d0b57f9c6fb4e7c57c21e681598803de044' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '858f8392a8786ef1c32c67b941cfbd1e208400a4' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            '31272d519abbc9010b05fa616dab1e2f1aff8ed3' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductRange',
            '7279fff89b515d10e6be87bfa9d435380d136974' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductReq',
            '01fef27732d3138044e188981d276abd68a3daff' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductSimple',
            '81fbde32d14219b68b3dab0a26395fb34b11f2e0' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductTwo',
            '09568eea900e3c3dcaf128839c4ccd5bbacc5e8d' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductType',
            'c0a246ed300be6a7cedef117ec4cdccdeccec3d2' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType',
            '2f19645f9a1e380831cbd113469e60864cd97a33' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType2',
            '983ee01b5ee5bd46c629b448ee41db75c0726ace' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '95bfdbcfdbb01de83e53e265f8bf40dd30c00da5' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            'd3a4a3ac9f09bf55dd6e05b676a907324cc3f4da' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductRange',
        );

        foreach ($hashes as $hash => $class) {
            $this->assertEquals($this->entitiesLocator->classToHash($class), $hash);
        }

        foreach ($hashes as $hash => $class) {
            $this->assertEquals($this->entitiesLocator->classToHash($class), $hash);
        }

        $class = 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceCustomerType2';

        // Test none cached class
        $this->assertEquals($this->entitiesLocator->classToHash($class), sha1($class));

        $this->assertEquals($this->entitiesLocator->classToHash($class), sha1($class));
    }

    public function testClassGetHashes()
    {
        $hashes = array (
            'c3309d0b57f9c6fb4e7c57c21e681598803de044' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '858f8392a8786ef1c32c67b941cfbd1e208400a4' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            '31272d519abbc9010b05fa616dab1e2f1aff8ed3' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductRange',
            '7279fff89b515d10e6be87bfa9d435380d136974' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductReq',
            '01fef27732d3138044e188981d276abd68a3daff' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductSimple',
            '81fbde32d14219b68b3dab0a26395fb34b11f2e0' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductTwo',
            '09568eea900e3c3dcaf128839c4ccd5bbacc5e8d' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductType',
            'c0a246ed300be6a7cedef117ec4cdccdeccec3d2' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType',
            '2f19645f9a1e380831cbd113469e60864cd97a33' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType2',
            '983ee01b5ee5bd46c629b448ee41db75c0726ace' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '95bfdbcfdbb01de83e53e265f8bf40dd30c00da5' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            'd3a4a3ac9f09bf55dd6e05b676a907324cc3f4da' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductRange',
        );

        $this->assertEquals($hashes, $this->entitiesLocator->getAllHashes());

        $hashesTrimmed = array (
            'c3309d0b57f' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '858f8392a87' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            '31272d519ab' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductRange',
            '7279fff89b5' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductReq',
            '01fef27732d' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductSimple',
            '81fbde32d14' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductTwo',
            '09568eea900' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductType',
            'c0a246ed300' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType',
            '2f19645f9a1' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductWithType2',
            '983ee01b5ee' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceInvoice',
            '95bfdbcfdbb' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductCompares',
            'd3a4a3ac9f0' => 'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\TestBundle\\Entity\\ECommerce\\ECommerceProductRange',
        );

        $this->assertEquals($hashesTrimmed, $this->entitiesLocator->getAllHashes(true));
    }
}
