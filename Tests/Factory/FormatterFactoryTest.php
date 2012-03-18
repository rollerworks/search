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

namespace Rollerworks\RecordFilterBundle\Tests\Factory;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\ArrayLoader;

use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;
use Rollerworks\RecordFilterBundle\Factory\FormatterFactory;

use Rollerworks\RecordFilterBundle\Tests\InvoiceType;

/**
 * Test the Validation generator. Its work is generating on-the-fly subclasses of a given model.
 * As you may have guessed, this is based on the Doctrine\ORM\Proxy module.
 */
class FormatterFactoryTest extends FactoryTestCase
{
    function testOneField()
    {
        $formatter = $this->getFormatter('ECommerceProductSimple');

        $this->assertEquals(array(
            'id' => new FilterConfig(null, false, false, false)
        ), $formatter->getFiltersConfig());

    }

    function testTwoFields()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $this->assertEquals(array(
            'id' => new FilterConfig(null, false, false, false),
            'name' => new FilterConfig(null, false, false, false)
        ), $formatter->getFiltersConfig());
    }

    function testReq()
    {
        $formatter = $this->getFormatter('ECommerceProductReq');

        $this->assertEquals(array(
            'id' => new FilterConfig(null, true),
            'name' => new FilterConfig(null)
        ), $formatter->getFiltersConfig());
    }

    function testWithType()
    {
        $formatter = $this->getFormatter('ECommerceProductType');

        $this->assertEquals(array(
            'id' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), true, false, false),
            'name' => new FilterConfig(null, false, false, false)
        ), $formatter->getFiltersConfig());
    }

    function testWithTypeAndConstructor()
    {
        $container = $this->createContainer();

        $formatter = $this->getFormatter('ECommerceProductWithType');
        $formatter->setContainer($container);

        $this->assertEquals(array(
            'id'         => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), false, false, false),
            'event_date' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\DateTime(), false, false, false)
        ), $formatter->getFiltersConfig());
    }

    function testWithTypeAndConstructor2()
    {
        $formatter = $this->getFormatter('ECommerceProductWithType2');

        $this->assertEquals(array(
            'id'         => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), false, false, false),
            'event_date' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\DateTime(true), false, false, false)
        ), $formatter->getFiltersConfig());
    }

    function testAcceptRanges()
    {
        $formatter = $this->getFormatter('ECommerceProductRange');

        $this->assertEquals(array(
            'id' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), true, true, false),
            'name' => new FilterConfig(null, false, false, false)
        ), $formatter->getFiltersConfig());
    }

    function testAcceptCompares()
    {
        $formatter = $this->getFormatter('ECommerceProductCompares');

        $this->assertEquals(array(
            'id' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), true, true, true),
            'name' => new FilterConfig(null, false, false, false)
        ), $formatter->getFiltersConfig());

        $input = new QueryInput();

        $input->setQueryString('id=2;');
        $this->assertTrue($formatter->formatInput($input));

        $input->setQueryString('name=me;');
        $this->assertFalse($formatter->formatInput($input));
    }

    function testGenerateClasses()
    {
        $this->assertFileNotExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductSimple/Formatter.php');
        $this->assertFileNotExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductCompares/Formatter.php');

        $this->formatterFactory->generateClasses(array(
            'Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductSimple',
            'Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductCompares'
        ));

        $this->assertFileExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductSimple/Formatter.php');
        $this->assertFileExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductCompares/Formatter.php');
    }

    function testObjectInput()
    {
        $formatter = $this->formatterFactory->getFormatter(new \Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductCompares());

        $this->assertInstanceOf('\\RecordFilter\\RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductCompares\\Formatter', $formatter);

        $this->assertEquals(array(
            'id' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), true, true, true),
            'name' => new FilterConfig(null, false, false, false)
        ), $formatter->getFiltersConfig());

        $input = new QueryInput();

        $input->setQueryString('id=2;');
        $this->assertTrue($formatter->formatInput($input));

        $input->setQueryString('name=me;');
        $this->assertFalse($formatter->formatInput($input));
    }

    function testWithContainer()
    {
        $oContainer = $this->createContainer();

        $formatter = $this->getFormatter('ECommerceInvoice');
        $formatter->setContainer($oContainer);

        $oInvoiceType = new InvoiceType();
        $oInvoiceType->setContainer($oContainer);

        $this->assertEquals(array(
            'id' => new FilterConfig(new \Rollerworks\RecordFilterBundle\Formatter\Type\Number(), false, false, false),
            'label' => new FilterConfig($oInvoiceType, false, false, false)
        ), $formatter->getFiltersConfig());

    }

    public function testOverwriteTranslator()
    {
        $translator = new Translator('en', new MessageSelector());
        $translator->addLoader('array', new ArrayLoader());

        $translator->addResource( 'array', array( 'record_filter' => array(
            'duplicate'          => '{0} Duplicate value \'%value%\' in field \'%field%\' (removed).|Duplicate value \'%value%\' in field \'%field%\' (removed) in group %group%.',
            'in_range'           => '{0} Value \'%value%\' in field \'%field%\' is also in range \'%range%\'.|Value \'%value%\' in field \'%field%\' is also in range \'%range%\' in group %group%.',
            'merged'             => '{0} Merged \'%field%\' to \'%destination%\'.|Merged \'%field%\' to \'%destination%\' in group %group%.',
            'no_range_support'   => '{0} Field \'%field%\' does not accept ranges.|Field \'%field%\' does not accept ranges in group %group%.',
            'not_lower'          => '{0} Validation error in field \'%field%\': \'%value1%\' is not lower then \'%value2%\'|Validation error in field \'%field%\': \'%value1%\' is not lower then \'%value2%\' in group %group%',
            'parse_error'        => '{0} Failed to parse of values of \'%field%\', possible syntax error.|Failed to parse of values of \'%field%\', possible syntax error in group %group%.',
            'range_overlap'      => '{0} Range \'%range1%\' in field \'%field%\' is overlapping in range \'%range2%\'.|Range \'%range1%\' in field \'%field%\' is overlapping in range \'%range2%\' in group %group%.',
            'required'           => '{0} Field \'%field%\' is required.|Field \'%field%\' is required in group %group%.',
            'validation_warning' => '{0} Validation error(s) in field \'%field%\' at value \'%value%\': %msg%|Validation error(s) in field \'%field%\' at value \'%value%\' in group %group%: %msg%',
        )), 'en' );

        $this->formatterFactory->getFormatter('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductSimple', $translator);
    }

    public function testNoTranslator()
    {
        $this->formatterFactory = new FormatterFactory(new \Doctrine\Common\Annotations\AnnotationReader(), __DIR__ . '/_generated', 'RecordFilter', true );

        $this->setExpectedException('\\RuntimeException', 'No Translator configured/given.');
        $this->formatterFactory->getFormatter('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductSimple');
    }
}