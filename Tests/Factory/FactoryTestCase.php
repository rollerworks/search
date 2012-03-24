<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
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