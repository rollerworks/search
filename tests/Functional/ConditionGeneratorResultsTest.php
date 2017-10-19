<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Elasticsearch\Functional;

use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Input\StringQueryInput;

/**
 * Class ConditionGeneratorResultsTest.
 */
class ConditionGeneratorResultsTest extends FunctionalElasticsearchTestCase
{
    /**
     * @var StringQueryInput
     */
    private $inputProcessor;

    protected function setUp()
    {
        parent::setUp();
        $this->inputProcessor = new StringQueryInput();
    }

    /**
     * @test
     */
    public function it_finds_with_id()
    {
        $this->makeTest('id: 1, 5;', [1, 5]);
    }

    /**
     * @test
     */
    public function it_finds_with_combined_field()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('customer-name: Pang, Leroy;', [1, 2, 3, 4]);
    }

    /**
     * @test
     */
    public function it_finds_with_range_and_excluding()
    {
        $this->makeTest('id: 1~7, !2;', [1, 3, 4, 5, 6]);
    }
    /**
     * @test
     */
    public function it_finds_by_customer_birthday()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('customer-birthday: "2000-05-15";', range(2, 4));
    }
    /**
     * @test
     */
    public function it_finds_by_customer_birthdays()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('customer-birthday: "2000-05-15", "1980-06-10";', [2, 3, 4]);
    }
    /**
     * @test
     */
    public function it_finds_with_or_group()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('* customer-birthday: "1980-11-20"; pub-date: "2015-05-01";', [1, 5]);
    }
    /**
     * @test
     */
    public function it_finds_pubDate_limited_by_price()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('pub-date: "2015-05-10"; total: "50.00"', [4]);
    }
    /**
     * @test
     */
    public function it_finds_by_customer_and_status()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('customer: 2; status: concept;', [3]);
    }
    /**
     * @test
     */
    public function it_finds_by_customer_and_status_and_total()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('customer: 2; status: paid; total: "90.00";', [2]);
    }
    /**
     * @test
     */
    public function it_finds_by_customer_and_status_or_price()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('customer: 2; *(status: paid; total: "50.00";)', [2, 4]);
    }
    /**
     * @test
     */
    public function it_finds_by_status_and_label_or_quantity_limited_by_price()
    {
        // Note there is no row with quantity 5, which is resolved as its in an OR'ed group
        $this->markTestSkipped('nested query support');
        $this->makeTest('status: published; *(row-quantity: 5; row-label: ~*"repair"; (row-price: "50.00"));', [4]);
    }
    /**
     * @test
     */
    public function it_finds_by_excluding_equals_pattern()
    {
        $this->markTestSkipped('nested query support');
        $this->makeTest('row-label: ~=Armor, ~=sword;', [2]); // Invoice 3 doesn't match as "sword" is lowercase
        $this->makeTest('row-price: "15.00"; row-label: ~!=Sword;', [5]);
        // Lowercase
        $this->makeTest('row-label: ~=Armor, ~i=sword;', [2, 3]);
    }

    /**
     * @param string $input
     * @param array  $expectedRows
     */
    private function makeTest($input, array $expectedRows)
    {
        $config = new ProcessorConfig($this->getFieldSet());
        try {
            $condition = $this->inputProcessor->process($config, $input);
            $this->assertDocumentsAreFound($condition, $expectedRows);
        } catch (\Exception $e) {
            self::detectSystemException($e);
            if (function_exists('dump')) {
                dump($e);
            } else {
                echo 'Please install symfony/var-dumper as dev-requirement to get a readable structure.'.PHP_EOL;
                // Don't use var-dump or print-r as this crashes php...
                echo get_class($e).'::'.(string) $e;
            }
            $this->fail('Condition contains errors.');
        }
    }
}
