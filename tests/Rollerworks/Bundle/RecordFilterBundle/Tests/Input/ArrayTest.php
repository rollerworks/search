<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Input;

use Rollerworks\Bundle\RecordFilterBundle\Input\ArrayInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class ArrayTest extends TestCase
{
    public function testSingleField()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput(array(array("user" => array("single-values" => array(2)))));

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'))))), $groups);
    }

    public function testSingleFieldWithUnicode()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('ß', FilterField::create('ß'));
        $input->setInput(array(array("ß" => array("single-values" => array(2)))));

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('ß' => new FilterValuesBag('ß', null, array(new SingleValue('2'))))), $group);
    }

    public function testSingleFieldWithUnicodeNumber()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('ß۲', FilterField::create('ß۲'));
        $input->setInput(array(array("ß۲" => array("single-values" => array(2)))));

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('ß۲' => new FilterValuesBag('ß۲', null, array(new SingleValue('2'))))), $group);
    }

    public function testMultipleFields()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setInput(array(array('user' => array('single-values' => array(2)), 'status' => array('single-values' => array('Active')))));

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2'))),
            'status' => new FilterValuesBag('status', null, array(new SingleValue('Active')))
        )), $group);
    }

    public function testOrGroup()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));

        $input->setInput(array(
            array(
                'user' => array('single-values' => array(2)),
                'status' => array('single-values' => array('Active;None')),
                'date' => array('single-values' => array('29-10-2010'))),
            array(
                'user' => array('single-values' => array(3)),
                'status' => array('single-values' => array('Concept')),
                'date' => array('single-values' => array('30-10-2010')))
            )
        );

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
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput(array(array('user' => array('excluded-values' => array(2)))));

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(), array(new SingleValue('2'))))), $groups);
    }

    public function testRanges()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));

        $input->setInput(array(
            array('user' => array('single-values' => array(2), 'ranges' => array(array('lower' => 10, 'upper' => 20)), 'excluded-ranges' => array(array('lower' => 30, 'upper' => 50)))))
        );

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2')), array(), array(new Range(10, 20)), array(), array(new Range(30, 50))),
        )), $group);
    }

    public function testComparisons()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput(array(
            array(
                'user' => array(
                    'single-values' => array(2),
                    'comparisons' => array(array('value' => '25.5.2010', 'operator' => '>')),
                )
            )
        ));

        $group = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', null, array(new SingleValue('2')), array(), array(), array(new Compare('25.5.2010', '>'))),
        )), $group);
    }

    public function testValidationNoRange()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('User', FilterField::create('User', null, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setInput(array(
            array('user' => array('single-values' => array(2), 'ranges' => array(array('lower' => 10, 'upper' => 20)), 'excluded-ranges' => array(array('lower' => 30, 'upper' => 50)))))
        );

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' does not accept ranges in group 1."), $input->getMessages());
    }

    public function testValidationNoCompare()
    {
        $input = new ArrayInput($this->translator);
        $input->setInput(array(
            array(
                'date' => array(
                    'single-values' => array(2),
                    'comparisons' => array(array('value' => '25.5.2010', 'operator' => '>')),
                )
            )
        ));

        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('date', FilterField::create('date', null, false, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' does not accept comparisons in group 1."), $input->getMessages());
    }

    public function testLimitGroups()
    {
        $input = new ArrayInput($this->translator);
        $input->setLimitGroups(2);

        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status', null, false, true, true));
        $input->setField('date', FilterField::create('date', null, false, true, true));

        $input->setInput(array(
            array(
                'date' => array(
                    'single-values' => array(2,3,5),
                )
            ),
            array(
                'date' => array(
                    'single-values' => array(2,3,5),
                )
            ),
            array(
                'date' => array(
                    'single-values' => array(2,3,5),
                )
            ),
        ));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Only 2 groups or less are accepted."), $input->getMessages());
    }

    public function testRequired()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user', null, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput(array(array('status' => array('single-values' => array('Active')))));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' is required in group 1."), $input->getMessages());
    }

    public function testRequired2()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user', null, true));
        $input->setField('status', FilterField::create('status'));
        $input->setInput(array(
            array('user' => array('single-values' => array(2)), 'status' => array('single-values' => array('Active'))),
            array('status' => array('single-values' => array('Active')))
        ));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' is required in group 2."), $input->getMessages());
    }

    public function testHash()
    {
        $input = new ArrayInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput(array(array("user" => array("single-values" => array(2)))));

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'))))), $groups);
        $this->assertEquals('af0fa098c3ad3357f8afb7059d23ca4e', $input->getHash());
    }

    public function testLimitValues()
    {
        $input = new ArrayInput($this->translator);
        $input->setLimitValues(2);

        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status', null, false, true, true));
        $input->setField('date', FilterField::create('date', null, false, true, true));

        $input->setInput(array(
            array(
                'date' => array(
                    'single-values' => array(2,3,5),
                )
            )
        ));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' in group 1 may only contain 2 values or less."), $input->getMessages());

        $input->setInput(array(
            array(
                'date' => array(
                    'single-values' => array(2,3),
                    'comparisons' => array(array('value' => '25.5.2010', 'operator' => '>')),
                )
            )
        ));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' in group 1 may only contain 2 values or less."), $input->getMessages());
    }

    public function testFieldNameAlias()
    {
        $input = new ArrayInput($this->translator);
        $input->setLabelToField('user', 'gebruikers');

        $input->setField('user', FilterField::create('user'));
        $input->setInput(array(array("gebruikers" => array("single-values" => array(2)))));

        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'))))), $groups);
    }

    public function testFieldNameAliasArray()
    {
        $input = new ArrayInput($this->translator);
        $input->setLabelToField('user', array('gebruikers', 'klanten'));

        $input->setField('user', FilterField::create('user'));
        $input->setInput(array(array("gebruikers" => array("single-values" => array(2)), "klanten" => array("single-values" => array(5)))));
        $groups = $input->getGroups();

        $this->assertEquals(array(), $input->getMessages());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', null, array(new SingleValue('2'), new SingleValue('5'))))), $groups);
    }
}
