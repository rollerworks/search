<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Functional;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BaseTestCase extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        $kernel = new AppKernel(
            isset($options['config']) ? $options['config'] : 'default.yml',
            isset($options['debug']) ? (boolean) $options['debug'] : true
        );

        return $kernel;
    }

    protected function tearDown()
    {
        $this->cleanTmpDir();
    }

    protected function setUp()
    {
        $this->cleanTmpDir();
    }

    private function cleanTmpDir()
    {
        $fs = new Filesystem();
        $fs->remove(getenv('TMPDIR') . '/RecordFilterBundle/');
    }
}
