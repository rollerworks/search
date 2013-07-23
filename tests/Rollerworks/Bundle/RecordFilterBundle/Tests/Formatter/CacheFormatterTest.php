<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter;

use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Type\Number;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\CacheFormatter;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter\Modifier\ModifierTestCase;
use Doctrine\Common\Cache\ArrayCache;

class CacheFormatterTest extends ModifierTestCase
{
    public function testCacheFormatterBasic()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        $input->setInput('User=2,5,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $cacheFormatter = new CacheFormatter(new ArrayCache());
        $cacheFormatter->setFormatter($formatter);

        if (!$cacheFormatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,5,10-20', array(new SingleValue('2'), new SingleValue('5')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')));
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
        $this->assertEquals($filters, $cacheFormatter->getFilters());
    }

    public function testFormatterByInput()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('period', FilterField::create('period', new Date(), false, true));

        $input->setInput('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $formatter = $this->newFormatter(false);
        $cacheFormatter = new CacheFormatter(new ArrayCache());
        if (!$cacheFormatter->formatInput($input, $formatter)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-"20"', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $this->assertEquals($expectedValues, $filters[0]);
        $this->assertEquals($filters, $cacheFormatter->getFilters());
    }

    public function testFormatterCached()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('period', FilterField::create('period', new Date(), false, true));
        $input->setInput('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $cacheDriver = new ArrayCache();

        $formatter = $this->newFormatter(false);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        if (!$cacheFormatter->formatInput($input, $formatter)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-"20"', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $this->assertEquals($expectedValues, $filters[0]);
        $this->assertEquals($filters, $cacheFormatter->getFilters());

        // This should be cached, normally this is not recommended
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('period', FilterField::create('period', new Date(), false, true));
        $input->setInput('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $cacheFormatter = new CacheFormatter($cacheDriver);
        if (!$cacheFormatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $this->assertEquals($filters, $cacheFormatter->getFilters());
    }

    public function testFormatterNotCached()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('period', FilterField::create('period', new Date(), false, true));

        $input->setInput('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $cacheDriver = new ArrayCache();

        $formatter = $this->newFormatter(false);
        $cacheFormatter = new CacheFormatter($cacheDriver);
        if (!$cacheFormatter->formatInput($input, $formatter)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $this->assertEquals($formatter->getFilters(), $cacheFormatter->getFilters());

        // This should not be cached
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $input->setInput('User=2,3,10-"20"; Status=Removed; period=29.10.2010');

        $this->setExpectedException('\RuntimeException', 'There is no result in the cache and no formatter is set for delegating.');
        $cacheFormatter->formatInput($input);
    }

    /**
     * Tests to make sure that an equal fieldset but
     * with different field-options does not provide the same caching key.
     */
    public function testFormatterTypeOptions()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('period', FilterField::create('period', new Date(), false, true));
        $input->setInput('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $cacheDriver = new ArrayCache();
        $formatter = $this->newFormatter(false);

        $cacheFormatter = new CacheFormatter($cacheDriver);
        if (!$cacheFormatter->formatInput($input, $formatter)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $cacheKey = $cacheFormatter->getCacheKey();

        $input->getFieldSet()->get('user')->getType()->setOptions(array('max' => 100));
        $cacheFormatter = new CacheFormatter($cacheDriver);
        $cacheFormatter->setFormatter($formatter);

        if (!$cacheFormatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $this->assertNotEquals($cacheKey, $cacheFormatter->getCacheKey());
        $this->assertEquals($formatter->getFilters(), $cacheFormatter->getFilters());
    }
}
