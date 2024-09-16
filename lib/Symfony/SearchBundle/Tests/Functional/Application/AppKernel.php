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

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional\Application;

use ApiPlatform\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Rollerworks\Bundle\SearchBundle\RollerworksSearchBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    use MicroKernelTrait;

    private string $config;

    public function __construct(string $config, $debug = true)
    {
        if (! (new Filesystem())->isAbsolutePath($config)) {
            $config = __DIR__ . '/config/' . $config;
        }

        if (! file_exists($config)) {
            throw new \RuntimeException(\sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;

        parent::__construct('test', $debug);
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    protected function getContainerClass(): string
    {
        $class = 'RSAppKernel' . ContainerBuilder::hash($this->config);
        $class = str_replace('\\', '_', $class) . ucfirst($this->environment) . ($this->debug ? 'Debug' : '') . 'Container';

        if (! preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $class)) {
            throw new \InvalidArgumentException(\sprintf('The environment "%s" contains invalid characters, it can only contain characters allowed in PHP class names.', $this->environment));
        }

        return $class;
    }

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            // new \Symfony\Bundle\TwigBundle\TwigBundle(),

            new RollerworksSearchBundle(),
        ];

        if (class_exists(DoctrineBundle::class)) {
            $bundles[] = new DoctrineBundle();
        }

        if (mb_substr($this->config, -16) === 'api_platform.yml') {
            $bundles[] = new TwigBundle();
            $bundles[] = new ApiPlatformBundle();
        }

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->config);
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir() . '/var/cache/' . mb_substr(sha1($this->config), 0, 6);
    }
}
