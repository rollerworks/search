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

use ApiPlatform\Core\Bridge\Symfony\Bundle\ApiPlatformBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use FOS\ElasticaBundle\FOSElasticaBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config, $debug = true)
    {
        if (! (new Filesystem())->isAbsolutePath($config)) {
            $config = __DIR__ . '/config/' . $config;
        }

        if (! \file_exists($config)) {
            throw new \RuntimeException(\sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;

        parent::__construct('test', $debug);
    }

    public function getName()
    {
        return 'RSearch' . \mb_substr(\sha1($this->config), 0, 3);
    }

    public function registerBundles()
    {
        $bundles = [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            //new \Symfony\Bundle\TwigBundle\TwigBundle(),

            new \Rollerworks\Bundle\SearchBundle\RollerworksSearchBundle(),
            new AppBundle\AppBundle(),
        ];

        if (\class_exists(DoctrineBundle::class)) {
            $bundles[] = new DoctrineBundle();
        }

        if (\class_exists(FOSElasticaBundle::class)) {
            $bundles[] = new FOSElasticaBundle();
        }

        if (\mb_substr($this->config, -16) === 'api_platform.yml') {
            $bundles[] = new TwigBundle();
            $bundles[] = new ApiPlatformBundle();
        }

        return $bundles;
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->setParameter('kernel.root_dir', __DIR__);
    }

    public function getRootDir()
    {
        if ($this->rootDir === null) {
            $this->rootDir = \str_replace('\\', '/', __DIR__);
        }

        return $this->rootDir;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        if (false === $tmpDir = \getenv('TMPDIR')) {
            $tmpDir = \sys_get_temp_dir();
        }

        return \rtrim($tmpDir, '/\\') . '/rollerworks-search-' . \sha1(__DIR__) . '/' . \mb_substr(\sha1($this->config), 0, 6);
    }
}
