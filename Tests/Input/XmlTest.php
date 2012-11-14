<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Input;

use Rollerworks\Bundle\RecordFilterBundle\Input\XmlInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class XmlTest extends TestCase
{
    public function testSingleField()
    {
        $input = new XmlInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>');

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'))))), $groups);
    }

    public function testMultipleFields()
    {
        $input = new XmlInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                    </field>
                    <field name="status">
                        <single-values>
                            <value>Active</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2'))),
            'status' => new FilterValuesBag('status', null, array(new SingleValue('Active')))
        )), $group);
    }

    public function testOrGroup()
    {
        $input = new XmlInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                    </field>
                    <field name="status">
                        <single-values>
                            <value>Active;None</value>
                        </single-values>
                    </field>
                    <field name="date">
                        <single-values>
                            <value>29-10-2010</value>
                        </single-values>
                    </field>
                </group>
                <group>
                    <field name="user">
                        <single-values>
                            <value>3</value>
                        </single-values>
                    </field>
                    <field name="status">
                        <single-values>
                            <value>Concept</value>
                        </single-values>
                    </field>
                    <field name="date">
                        <single-values>
                            <value>30-10-2010</value>
                        </single-values>
                    </field>
                </group>
            </groups>
        </filters>');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(
            array(
                'user' => new FilterValuesBag('user', null, array(new SingleValue('2'))),
                'status' => new FilterValuesBag('status', null, array(new SingleValue('Active;None'))),
                'date' => new FilterValuesBag('date', null, array(new SingleValue('29-10-2010'))),
            ),
            array(
                'user' => new FilterValuesBag('user', null, array(new SingleValue('3'))),
                'status' => new FilterValuesBag('status', null, array(new SingleValue('Concept'))),
                'date' => new FilterValuesBag('date', null, array(new SingleValue('30-10-2010'))),
            ),
        ), $group);
    }

    public function testSingleValueExclude()
    {
        $input = new XmlInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <excluded-values>
                            <value>2</value>
                        </excluded-values>
                    </field>
                </group>
            </groups>
        </filters>');

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(), array(new SingleValue('2'))))), $groups);
    }

    public function testRanges()
    {
        $input = new XmlInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                        <ranges>
                            <range>
                                <lower>10</lower>
                                <higher>20</higher>
                            </range>
                        </ranges>
                        <excluded-ranges>
                            <range>
                                <lower>30</lower>
                                <higher>50</higher>
                            </range>
                        </excluded-ranges>
                    </field>
                </group>
            </groups>
        </filters>');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2')), array(), array(new Range(10, 20)), array(), array(new Range(30, 50))),
        )), $group);
    }

    public function testComparisons()
    {
        $input = new XmlInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                        <compares>
                            <compare opr="&gt;">25.5.2010</compare>
                        </compares>
                    </field>
                </group>
            </groups>
        </filters>');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2')), array(), array(), array(new Compare('25.5.2010', '>'))),
        )), $group);
    }

    public function testValidationNoRange()
    {
        $input = new XmlInput($this->translator);
        $input->setField('User', FilterField::create('User', null, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                        </single-values>
                        <ranges>
                            <range>
                                <lower>10</lower>
                                <higher>20</higher>
                            </range>
                        </ranges>
                        <excluded-ranges>
                            <range>
                                <lower>30</lower>
                                <higher>50</higher>
                            </range>
                        </excluded-ranges>
                    </field>
                </group>
            </groups>
        </filters>');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' does not accept ranges in group 1."), $input->getMessages());
    }

    public function testValidationNoCompare()
    {
        $input = new XmlInput($this->translator);
        $input->setInput('<?xml version="1.0" encoding="UTF-8"?>
        <filters>
            <groups>
                <group>
                    <field name="user">
                        <single-values>
                            <value>2</value>
                            <value>3</value>
                        </single-values>
                        <ranges>
                            <range>
                                <lower>10</lower>
                                <higher>20</higher>
                            </range>
                        </ranges>
                    </field>
                    <field name="status">
                        <single-values>
                            <value>Active</value>
                        </single-values>
                    </field>
                    <field name="date">
                        <single-values>
                            <value>2</value>
                            <value>3</value>
                        </single-values>
                        <compares>
                            <compare opr="&gt;">25.5.2010</compare>
                        </compares>
                    </field>
                </group>
            </groups>
        </filters>');

        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', null, true, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' does not accept comparisons in group 1."), $input->getMessages());
    }
}
