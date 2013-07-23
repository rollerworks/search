<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TwigEngine;

/**
 * Abstract TestCase for Unit testing.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator = null;

    /**
     * @var string
     */
    private $currentLocale = null;

    protected function setUp()
    {
        $translator = new Translator('en', new MessageSelector());
        $translator->addLoader('xliff', new XliffFileLoader());
        $translator->addLoader('array', new ArrayLoader());

        $translator->addResource('xliff', __DIR__ . '/../../../../../src/Rollerworks/Bundle/RecordFilterBundle/Resources/translations/messages.en.xliff', 'en');
        $translator->addResource('xliff', __DIR__ . '/../../../../../src/Rollerworks/Bundle/RecordFilterBundle/Resources/translations/validators.en.xliff', 'en', 'validators');

        $this->translator = $translator;
        $this->setUpLocale();
    }

    protected function setUpLocale()
    {
        if (!$this->currentLocale) {
            $this->currentLocale = \Locale::getDefault();
        }
    }

    protected function restoreLocal()
    {
        if ($this->currentLocale) {
            \Locale::setDefault($this->currentLocale);
        }
    }

    protected function tearDown()
    {
        $this->restoreLocal();
    }

    /**
     * @return ContainerBuilder
     */
    protected function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__ . '/.cache' ,
            'kernel.charset'   => 'UTF-8',
            'kernel.debug'     => false,
        )));

        $container->set('service_container', $container);

        return $container;
    }

    /**
     * Copied from Symfony\Tests\Component\Config\Resource\DirectoryResourceTest
     *
     * @param $directory
     */
    protected function removeDirectory($directory)
    {
        $fs = new Filesystem();
        $fs->remove($directory);
    }

    protected function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());

        if (!$container->hasParameter('kernel.bundles')) {
            $container->setParameter('kernel.bundles', array());
        }

        $container->compile();
    }
}
