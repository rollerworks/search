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

use Rollerworks\Bundle\RecordFilterBundle\Input\JsonInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class JsonTest extends TestCase
{
    public function testSingleField()
    {
        $input = new JsonInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('[ { "user": { "single-values": [2] } } ]');

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'))))), $groups);
    }

    public function testSingleFieldWithUnicode()
    {
        $input = new JsonInput($this->translator);
        $input->setField('ß', FilterField::create('ß'));
        $input->setInput(json_encode(array(array("ß" => array("single-values" => array(2))))));

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('ß' => new FilterValuesBag('ß', null, array(new SingleValue('2'))))), $group);
    }

    public function testSingleFieldWithUnicodeNumber()
    {
        $input = new JsonInput($this->translator);
        $input->setField('ß۲', FilterField::create('ß۲'));
        $input->setInput(json_encode(array(array("ß۲" => array("single-values" => array(2))))));

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('ß۲' => new FilterValuesBag('ß۲', null, array(new SingleValue('2'))))), $group);
    }

    public function testMultipleFields()
    {
        $input = new JsonInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setInput('[ { "user": { "single-values": [2] }, "status": { "single-values": ["Active"] } } ]');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2'))),
            'status' => new FilterValuesBag('status', null, array(new SingleValue('Active')))
        )), $group);
    }

    public function testOrGroup()
    {
        $input = new JsonInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setInput('[
            {
                "user": { "single-values": [2] },
                "status": { "single-values": ["Active;None"] },
                "date": { "single-values": ["29-10-2010"] }
            },
            {
                "user": { "single-values": [3] },
                "status": { "single-values": ["Concept"] },
                "date": { "single-values": ["30-10-2010"] }
            }
        ]');

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
        $input = new JsonInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('[ { "user": { "excluded-values": [2] } } ]');

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(), array(new SingleValue('2'))))), $groups);
    }

    public function testRanges()
    {
        $input = new JsonInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput('[ { "user": { "single-values": [2], "ranges": [{ "lower": 10, "upper": 20 }], "excluded-ranges": [{ "lower": 30, "upper": 50 }] } } ]');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2')), array(), array(new Range(10, 20)), array(), array(new Range(30, 50))),
        )), $group);
    }

    public function testComparisons()
    {
        $input = new JsonInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput('[ { "user": { "single-values": [2], "comparisons": [{"value": "25.5.2010", "operator": ">"}] } } ]');

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2')), array(), array(), array(new Compare('25.5.2010', '>'))),
        )), $group);
    }

    public function testValidationNoRange()
    {
        $input = new JsonInput($this->translator);
        $input->setField('User', FilterField::create('User', null, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setInput('[ { "user": { "ranges": [{"lower": 2, "upper": 5}] }, "status": { "single-values": ["Active"] }, "date": { "single-values": ["29.10.2010"] } } ]');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' does not accept ranges in group 1."), $input->getMessages());
    }

    public function testValidationNoCompare()
    {
        $input = new JsonInput($this->translator);
        $input->setInput('[
            {
                "user": { "single-values": [2,3], "ranges": [{"lower": 10, "upper": 20}] },
                "status": { "single-values": ["Active"] },
                "date": { "single-values": ["29.10.2010"], "comparisons": [{"value": "25.5.2010", "operator": ">"}] } }
        ]');

        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', null, true, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' does not accept comparisons in group 1."), $input->getMessages());
    }

    public function testLimitGroups()
    {
        $input = new JsonInput($this->translator);
        $input->setLimitGroups(2);

        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status', null, false, true, true));
        $input->setField('date', FilterField::create('date', null, false, true, true, true));

        $input->setInput('[
            {
                "date": { "single-values": ["29.10.2010", "30.10.2010"], "comparisons": [{"value": "25.5.2010", "operator": ">"}] }
            },
            {
                "date": { "single-values": ["29.10.2010", "30.10.2010"], "comparisons": [{"value": "25.5.2010", "operator": ">"}] }
            },
            {
                "date": { "single-values": ["29.10.2010", "30.10.2010"], "comparisons": [{"value": "25.5.2010", "operator": ">"}] }
            }
        ]');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Only 2 groups or less are accepted."), $input->getMessages());
    }

    public function testLimitValues()
    {
        $input = new JsonInput($this->translator);
        $input->setLimitValues(2);

        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status', null, false, true, true));
        $input->setField('date', FilterField::create('date', null, false, true, true, true));

        $input->setInput('[
            {
                "date": { "single-values": ["29.10.2010", "30.10.2010"], "comparisons": [{"value": "25.5.2010", "operator": ">"}] }
            }
        ]');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' in group 1 may only contain 2 values or less."), $input->getMessages());
    }

    public function testFieldNameAlias()
    {
        $input = new JsonInput($this->translator);
        $input->setLabelToField('user', 'gebruikers');

        $input->setField('user', FilterField::create('user'));
        $input->setInput('[ { "gebruikers": { "single-values": [2] } } ]');

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'))))), $groups);
    }
}
