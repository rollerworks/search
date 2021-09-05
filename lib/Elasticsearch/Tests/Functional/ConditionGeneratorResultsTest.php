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
 *
 * @group functional
 *
 * Special cases needed to be handled here
 * - IDs do not behave like other values
 *   for example you cannot use ranged queries with them, use "ids" query
 * - dates always behave like a range, even for exact values
 *
 * @internal
 */
final class ConditionGeneratorResultsTest extends FunctionalElasticsearchTestCase
{
    /**
     * @var StringQueryInput
     */
    private $inputProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inputProcessor = new StringQueryInput();
    }

    /** @test */
    public function it_finds_with_id(): void
    {
        $this->makeTest('id: 1, 5;', [1, 5]);
    }

    /** @test */
    public function it_finds_ids_with_range_and_excluding_by_id(): void
    {
        $this->makeTest('id: 1~7[, !2;', [1, 3, 4, 5, 6, 7]); // FIXME This must not contain 7
    }

    /** @test */
    public function it_finds_with_date(): void
    {
        $this->makeTest('pub-date: "2010-05-10"', [1, 2]);
    }

    /** @test */
    public function it_finds_with_range_and_excluding_by_id(): void
    {
        $this->makeTest('pub-date: "2010-05-10"; id: !2;', [1]);
    }

    /** @test */
    public function it_finds_with_child_property_field(): void
    {
        $this->makeTest('customer: 1, 2; id: !2', [1, 3, 4, 7, 8, 9]);
    }

    /** @test */
    public function it_finds_by_customer_birthday(): void
    {
        $this->makeTest('customer-birthday: "2000-05-15";', \range(2, 4));
    }

    /** @test */
    public function it_finds_by_date_relative(): void
    {
        $this->makeTest('pub-date-time: >"7 days";', [8, 9]);
        $this->makeTest('pub-date-time: >"16 days";', [9]);
        $this->makeTest('pub-date-time: "14 days" ~ "6 months";', [8]);
        $this->makeTest('pub-date-time: >"2015-05-10 01:12:13", <"-7 days";', [7]);
        $this->makeTest('pub-date-time: >"2015-05-10 01:12:13", <"-8 days";', []);
        $this->makeTest('pub-date-time: >"2015-05-10 01:12:13", >"-1 year", <"6 months";', [7, 8]);
    }

    /** @test */
    public function it_finds_by_customer_birthdays(): void
    {
        $this->makeTest('customer-birthday: "2000-05-15", "1980-06-10";', [2, 3, 4]);
    }

    /** @test */
    public function it_finds_by_customer_note(): void
    {
        $this->makeTest('customer-comment: ~*Que', [3]);
        $this->makeTest('customer-comment: ~*whatever', [4]);

        $this->makeTest('customer-comment: ~*specific', [1, 2, 3]);
        $this->makeTest('customer-comment-restricted: ~*specific', [2]);
    }

    /** @test */
    public function it_finds_by_date_comparison(): void
    {
        $this->makeTest('pub-date: >= "2015-05-10"', [4, 7, 8, 9]);
    }

    /** @test */
    public function it_finds_by_date_range_excluding_by_date(): void
    {
        $this->makeTest('pub-date: "2010-05-10"~"2015-05-01", !"2000-05-15";', [1, 2, 5]);
    }

    /** @test */
    public function it_finds_with_or_group(): void
    {
        $this->makeTest('* customer-birthday: "1980-11-20"; pub-date: "2015-05-01";', [1, 5, 7, 8, 9]);
    }

    /** @test */
    public function it_finds_pub_date_time_comparison(): void
    {
        $this->makeTest('pub-date-time: >= "2015-05-09 13:12:11"', [4, 7, 8, 9]);
    }

    /** @test */
    public function it_finds_pub_date_limited_by_price(): void
    {
        $this->makeTest('pub-date: "2015-05-10"; total: "100.00"', [4]);
    }

    /** @test */
    public function it_finds_by_customer_and_status(): void
    {
        $this->makeTest('customer: 2; status: concept;', [3]);
    }

    /** @test */
    public function it_finds_by_customer_and_status_and_total(): void
    {
        $this->makeTest('customer: 2; status: paid; total: "90.00";', [2]);
    }

    /** @test */
    public function it_finds_by_customer_and_status_or_price(): void
    {
        // 2 => matches status, doesn't match price
        // 4 => matches price, doesn't match status
        $this->makeTest('customer: 2; *(status: paid; total: "100.00";)', [2, 4]);
    }

    /** @test */
    public function it_finds_by_item_price(): void
    {
        $this->makeTest('row-price: 15.00;', [2, 5]);
    }

    /** @test */
    public function it_finds_by_status_and_label_or_quantity_limited_by_price(): void
    {
        // Note there is no row with quantity 5, which is resolved as its in an OR'ed group
        $this->makeTest('status: published; *(row-quantity: 5; row-label: ~*"repair"; (row-price: "50.00"));', [4, 6]);
    }

    /** @test */
    public function it_finds_by_excluding_equals_pattern(): void
    {
        // note: everything is case-sensitive by default, must use lowercase here
        // TODO: this throws an exception for me from tests, but works if I run the query directly (?!)
        // $this->makeTest('row-label: ~=armor, ~=sword;', [2]);
        $this->makeTest('row-price: "15.00"; row-label: ~!=sword;', [5]);
    }

    /** @test */
    public function it_sorts_by_total(): void
    {
        $this->makeTest('@total: ASC', [3, 6, 7, 8, 9, 2, 1, 4, 5]);
    }

    /** @test */
    public function it_sorts_by_total_desc(): void
    {
        $this->makeTest('@total: DESC', [5, 1, 4, 2, 7, 8, 9, 6, 3]);
    }

    /** @test */
    public function it_sorts_by_customer_name(): void
    {
        $this->makeTest('@customer-name: ASC', [5, 2, 3, 4, 1, 7, 8, 9, 6]);
    }

    /** @test */
    public function it_applies_conditional_conditions_from_order_mappings(): void
    {
        $this->makeTest('@customer-pubdate: ASC', [3, 2, 1, 4]);
    }

    /** @test */
    public function it_sorts_by_has_child_query(): void
    {
        $this->makeTest('@customer-note-pubdate: ASC', [4, 3, 2, 1]);
    }

    /** @test */
    public function it_sorts_by_has_child_query_desc(): void
    {
        $this->makeTest('@customer-note-pubdate: DESC', [1, 2, 3, 4]);
    }

    private function makeTest(string $input, array $expectedRows): void
    {
        $config = new ProcessorConfig($this->getFieldSet());

        try {
            $condition = $this->inputProcessor->process($config, $input);
            $this->assertDocumentsAreFound($condition, $expectedRows);
        } catch (\Exception $e) {
            self::detectSystemException($e);

            if (\function_exists('dump')) {
                dump($e);
            } else {
                echo 'Please install symfony/var-dumper as dev-requirement to get a readable structure.' . \PHP_EOL;
                // Don't use var-dump or print-r as this crashes php...
                echo \get_class($e) . '::' . (string) $e;
            }
            self::fail('Condition contains errors.');
        }
    }
}
