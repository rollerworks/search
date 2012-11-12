<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Exporter;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\Exporter\XmlExporter;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class XmlTest extends TestCase
{
    public function testOneGroupOneField()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('user=1;');

        $this->assertTrue($formatter->formatInput($input));

        $this->assertXmlStringEqualsXmlString('<'.'?xml version="1.0" encoding="utf-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>1</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>', $this->getXMLDumper($formatter));
    }

    public function testTwoGroupsOneField()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('(user=1;),(user=2;)');

        $this->assertTrue($formatter->formatInput($input));

        $this->assertXmlStringEqualsXmlString('<'.'?xml version="1.0" encoding="utf-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>1</value>
                        </single-values>
                    </field>
                </group>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>', $this->getXMLDumper($formatter));
    }

    public function testOneGroupTwoFields()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice'));
        $input->setInput('user=1; invoice="F2012-800";');

        $this->assertTrue($formatter->formatInput($input));

        $this->assertXmlStringEqualsXmlString('<'.'?xml version="1.0" encoding="utf-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>1</value>
                        </single-values>
                    </field>
                    <field name="invoice">
                        <single-values>
                            <value>F2012-800</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>', $this->getXMLDumper($formatter));
    }

    public function testTwoGroupsTwoFields()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice'));
        $input->setInput('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');

        $this->assertTrue($formatter->formatInput($input));

        $this->assertXmlStringEqualsXmlString('<'.'?xml version="1.0" encoding="utf-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>1</value>
                        </single-values>
                    </field>
                    <field name="invoice">
                        <single-values>
                            <value>F2010-4242</value>
                        </single-values>
                    </field>
                </group>
                    <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                    </field>
                    <field name="invoice">
                        <single-values>
                            <value>F2012-4242</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>', $this->getXMLDumper($formatter));
    }

    public function testRangeValue()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice', null, false, true));
        $input->setInput('(user=1; invoice="F2010-4242"-"F2012-4245";),(user=2; invoice="F2012-4248";)');

        $this->assertTrue($formatter->formatInput($input));

        $this->assertXmlStringEqualsXmlString('<'.'?xml version="1.0" encoding="utf-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>1</value>
                        </single-values>
                    </field>
                    <field name="invoice">
                        <ranges>
                            <range>
                                <lower>F2010-4242</lower>
                                <higher>F2012-4245</higher>
                            </range>
                        </ranges>
                    </field>
                </group>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                    </field>
                    <field name="invoice">
                        <single-values>
                            <value>F2012-4248</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>', $this->getXMLDumper($formatter));
    }

    public function testCompareValue()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('invoice', FilterField::create('invoice'));
        $input->setInput('(user=>1,<>2,>=5,<8,<=9;)');

        $this->assertTrue($formatter->formatInput($input));

        $this->assertXmlStringEqualsXmlString('<'.'?xml version="1.0" encoding="utf-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <compares>
                            <compare opr="&gt;">1</compare>
                            <compare opr="&lt;&gt;">2</compare>
                            <compare opr="&gt;=">5</compare>
                            <compare opr="&lt;">8</compare>
                            <compare opr="&lt;=">9</compare>
                        </compares>
                    </field>
                </group>
            </groups>
        </filters>', $this->getXMLDumper($formatter));
    }

    /**
     * Retrieves libxml errors and clears them.
     *
     * @see \Symfony\Component\Routing\Loader\XmlFileLoader
     *
     * @return array An array of libxml error strings
     */
    protected function getXmlErrors()
    {
        $errors = array();
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf('[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ? $error->file : 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();

        return $errors;
    }

    /**
     * @param Formatter $formatter
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected function getXMLDumper(Formatter $formatter)
    {
        $dumper = new XmlExporter();
        $output = $dumper->dumpFilters($formatter);

        $location = realpath(__DIR__ . '/../../Input/schema/dic/input/xml-input-1.0.xsd');

        $dom = new \DOMDocument();
        $dom->loadXML($output);

        $current = libxml_use_internal_errors(true);
        if (!$dom->schemaValidate($location)) {
            throw new \InvalidArgumentException( $output . ': ' . implode("\n", $this->getXmlErrors()));
        }
        libxml_use_internal_errors($current);

        return $output;
    }
}
