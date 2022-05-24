<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class FunctionalTestCase extends WebTestCase
{
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new Application\AppKernel(
            $options['config'] ?? 'default.yml',
            false
        );
    }

    protected static function getKernelClass(): string
    {
        return Application\AppKernel::class;
    }

    protected static function newClient(array $options = [], array $server = []): KernelBrowser
    {
        $client = static::createClient(array_merge(['config' => 'default.yml'], $options), $server);

        $warmer = $client->getContainer()->get('cache_warmer');
        $warmer->enableOptionalWarmers();
        $warmer->warmUp($client->getContainer()->getParameter('kernel.cache_dir'));

        return $client;
    }
}
