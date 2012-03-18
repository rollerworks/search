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

namespace Rollerworks\RecordFilterBundle\Tests;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

use Rollerworks\RecordFilterBundle\Tests\TwigEngine;
use Symfony\Component\Templating\TemplateNameParserInterface;
use Twig_Loader_Filesystem, Twig_Environment;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Translation\Translator
     */
    protected $translator = null;

    protected function setUp()
    {
        $translator = new Translator('en', new MessageSelector());
        $translator->addLoader('xliff', new XliffFileLoader());
        $translator->addLoader('array', new ArrayLoader());

        $translator->addResource('xliff', __DIR__ . '../../Resources/translations/messages.en.xliff', 'en');

        $this->translator = $translator;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__,
            'kernel.charset'   => 'UTF-8',
            'kernel.debug'     => false,
        )));

        $container->set('service_container', $container);

        return $container;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    protected function getTwigInstance()
    {
        $config = array('cache' => __DIR__ . '/_TwigCache', 'strict_variables' => true);
        $loader = new Twig_Loader_Filesystem(array(__DIR__ . '/Fixtures/Views'));

        $twig = new Twig_Environment($loader, $config);
        $twig->addExtension(new \Twig_Extensions_Extension_Intl());

        $engine = new TwigEngine($twig);

        return $engine;
    }
}