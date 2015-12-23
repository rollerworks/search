<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    protected static function createKernel(array $options = [])
    {
        return new Application\AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml'
        );
    }

    protected static function getKernelClass()
    {
        return 'Rollerworks\Bundle\SearchBundle\Tests\Functional\Application\AppKernel';
    }

    /**
     * @param array $options
     * @param array $server
     *
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static function newClient(array $options = [], array $server = [])
    {
        $client = static::createClient(array_merge(['config' => 'default.yml'], $options), $server);

        $warmer = $client->getContainer()->get('cache_warmer');
        $warmer->warmUp($client->getContainer()->getParameter('kernel.cache_dir'));
        $warmer->enableOptionalWarmers();

        return $client;
    }
}
