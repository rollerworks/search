<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Factory;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\XliffFileLoader;

use Rollerworks\RecordFilterBundle\Factory\FormatterFactory;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

require_once __DIR__ . '/TestInit.php';

/**
 * Test the Validation generator. Its work is generating on-the-fly subclasses of a given model.
 * As you may have guessed, this is based on the Doctrine\ORM\Proxy module.
 */
abstract class FactoryTestCase extends \Rollerworks\RecordFilterBundle\Tests\TestCase
{
    /**
     * @var \Rollerworks\RecordFilterBundle\Factory\FormatterFactory
     */
    protected $formatterFactory;

    /**
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    protected $annotationReader;

    protected function setUp()
    {
        parent::setUp();

        $this->annotationReader = new \Doctrine\Common\Annotations\AnnotationReader();
        $this->formatterFactory = new FormatterFactory($this->annotationReader, __DIR__ . '/_generated', 'RecordFilter', true );

        $this->formatterFactory->setTranslator($this->translator);
    }

    protected function tearDown()
    {
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__ . '/_generated'), \RecursiveIteratorIterator::CHILD_FIRST) as $path)
        {
            if ($path->isDir()) {
                rmdir($path->__toString());
            }
            else {
                unlink($path->__toString());
            }
        }
    }

    /**
     * @param string $class
     * @return \Rollerworks\RecordFilterBundle\Formatter\Formatter
     */
    protected function getFormatter($class)
    {
        $formatter = $this->formatterFactory->getFormatter('Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\' . $class);
        $this->assertInstanceOf('RecordFilter\\RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerce' . $class . '\\Formatter', $formatter);

        return $formatter;
    }
}