<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    private $config;

    public function __construct($config, $debug = true)
    {
        parent::__construct('test', $debug);

        $fs = new Filesystem();
        if (!$fs->isAbsolutePath($config)) {
            $config = __DIR__ . '/config/' . $config;
        }

        if (!file_exists($config)) {
            throw new \RuntimeException(sprintf('The config file "%s" does not exist.', $config));
        }

        $this->config = $config;
    }

    public function registerBundles()
    {
        return array(
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),

            new \Rollerworks\Bundle\CacheBundle\RollerworksCacheBundle(),
            new \Rollerworks\Bundle\RecordFilterBundle\RollerworksRecordFilterBundle(),
            new \Rollerworks\Bundle\RecordFilterBundle\Tests\Functional\Bundle\UserBundle\AcmeUserBundle()
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->config);
    }

    public function getCacheDir()
    {
        return getenv('TMPDIR') . '/RecordFilterBundle/' . substr(sha1($this->config), 0, 6);
    }

    public function serialize()
    {
        return serialize(array($this->config, $this->isDebug()));
    }

    public function unserialize($str)
    {
        call_user_func_array(array($this, '__construct'), unserialize($str));
    }
}
