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
