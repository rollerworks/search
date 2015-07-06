<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Unit\Processor;

use Doctrine\Common\Cache\Cache;
use Prophecy\Argument;
use Rollerworks\Bundle\SearchBundle\Processor\CacheSearchProcessor;
use Rollerworks\Bundle\SearchBundle\Processor\SearchProcessorInterface;
use Rollerworks\Component\Search\Exporter;
use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ExporterFactory;
use Rollerworks\Component\Search\FieldLabelResolver\NoopLabelResolver;
use Rollerworks\Component\Search\FieldSetRegistry;
use Rollerworks\Component\Search\Input;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\SingleValue;
use Symfony\Component\HttpFoundation\Request;

final class CacheSearchProcessorTest extends SearchIntegrationTestCase
{
    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|SearchProcessorInterface
     */
    private $processor;

    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    /**
     * @var SearchConditionSerializer
     */
    private $conditionSerializer;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|ExporterFactory
     */
    private $exporterFactory;

    /**
     * @var SearchCondition
     */
    private $condition;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|Cache
     */
    private $cache;

    protected function setUp()
    {
        parent::setUp();

        $fieldSet = $this->getFieldSet(true);

        if (!$fieldSet->isConfigLocked()) {
            $fieldSet->lockConfig();
        }

        $this->fieldSetRegistry = new FieldSetRegistry();
        $this->fieldSetRegistry->add($fieldSet);

        $this->conditionSerializer = new SearchConditionSerializer($this->fieldSetRegistry);

        $this->exporterFactory = $this->prophesize('Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ExporterFactory');
        $this->exporterFactory->create(Argument::any())->will(
            function ($args) {
                switch ($args[0]) {
                    case 'filter_query':
                        return new Exporter\FilterQueryExporter(new NoopLabelResolver());

                    case 'xml':
                        return new Exporter\XmlExporter(new NoopLabelResolver());

                    default:
                        throw new \InvalidArgumentException(
                            sprintf('%s is not supported for this test case', $args[0])
                        );
                }
            }
        );

        $this->processor = $this->prophesize('Rollerworks\Bundle\SearchBundle\Processor\SearchProcessorInterface');
        $this->cache = $this->prophesize('Doctrine\Common\Cache\Cache');

        $this->condition = SearchConditionBuilder::create($fieldSet)
                ->field('name')
                    ->addSingleValue(new SingleValue('user'))
                ->end()
            ->getSearchCondition()
        ;
    }

    private function createProcessor($uriPrefix = '')
    {
        $config = new Input\ProcessorConfig($this->fieldSetRegistry->get('test'));
        $processor = new CacheSearchProcessor(
            $this->processor->reveal(),
            $this->conditionSerializer,
            $this->exporterFactory->reveal(),
            $this->cache->reveal(),
            $config,
            $uriPrefix
        );

        return $processor;
    }

    public function testProcessingEmptyRequestIsValid()
    {
        $this->processor->processRequest(Argument::any())->shouldNotBeCalled();
        $this->cache->contains(Argument::any())->shouldNotBeCalled();

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list'));

        $this->assertTrue($processor->isValid());
        $this->assertEquals([], $processor->getErrors());
        $this->assertEquals('', $processor->getSearchCode());

        // This should be 'empty' as the filter doesn't apply for the processor
        $processor2 = $this->createProcessor('auth');
        $processor2->processRequest(Request::create('/list?filter=foo'));
        $this->assertNull($processor->exportSearchCondition('filter_query'));

        $this->assertTrue($processor2->isValid());
        $this->assertEquals('', $processor2->getSearchCode());
        $this->assertNull($processor2->exportSearchCondition('filter_query'));
    }

    public function testProcessSearchCodeFromQueryWithNoCache()
    {
        // No cache exists
        $this->cache->contains('search_condition.test.bmFtZTogdXNlcjs')->willReturn(false);

        // Execute the real processor
        $this->processor->processRequest(Argument::any())->willReturn($this->processor)->shouldBeCalled();
        $this->processor->isValid()->willReturn(true)->shouldBeCalled();
        $this->processor->getSearchCondition()->willReturn($this->condition);
        $this->processor->getSearchCode()->willReturn('bmFtZTogdXNlcjs');

        // And store processed condition
        $this->cache->save('search_condition.test.bmFtZTogdXNlcjs', Argument::any())->shouldBeCalled();

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter=bmFtZTogdXNlcjs'));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
    }

    public function testProcessSearchCodeFromQueryWithExistingCache()
    {
        $this->processor->processRequest(Argument::any())->shouldNotBeCalled();

        $this->cache->contains('search_condition.test.bmFtZTogdXNlcjs')->willReturn(true);
        $this->cache->fetch('search_condition.test.bmFtZTogdXNlcjs')->willReturn(
            $this->conditionSerializer->serialize($this->condition)
        );

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter=bmFtZTogdXNlcjs'));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
    }

    public function testExportSearchConditionWithNoExportedCache()
    {
        // Processor
        $this->processor->processRequest(Argument::any())->shouldNotBeCalled();
        $this->cache->contains('search_condition.test.bmFtZTogdXNlcjs')->willReturn(true);
        $this->cache->fetch('search_condition.test.bmFtZTogdXNlcjs')->willReturn(
            $this->conditionSerializer->serialize($this->condition)
        );

        // Exporter
        $this->cache->contains('search_export.bmFtZTogdXNlcjs.filter_query')->willReturn(false);
        $this->cache->fetch('search_export.bmFtZTogdXNlcjs.formats')->willReturn(false);

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter=bmFtZTogdXNlcjs'));

        // Make sure the exported condition is stored
        $this->cache->save('search_export.bmFtZTogdXNlcjs.filter_query', 'name: user;')->shouldBeCalled();
        $this->cache->save('search_export.bmFtZTogdXNlcjs.formats', ['filter_query' => true])->shouldBeCalled();

        $exported = $processor->exportSearchCondition('filter_query');

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
        $this->assertEquals('name: user;', $exported);
    }

    public function testExportSearchConditionWithExportedCache()
    {
        // Processor
        $this->processor->processRequest(Argument::any())->shouldNotBeCalled();
        $this->cache->contains('search_condition.test.bmFtZTogdXNlcjs')->willReturn(true);
        $this->cache->fetch('search_condition.test.bmFtZTogdXNlcjs')->willReturn(
            $this->conditionSerializer->serialize($this->condition)
        );

        // Exporter
        $this->cache->contains('search_export.bmFtZTogdXNlcjs.filter_query')->willReturn(true);
        $this->cache->fetch('search_export.bmFtZTogdXNlcjs.filter_query')->willReturn('name: user;');

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter=bmFtZTogdXNlcjs'));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
        $this->assertEquals('name: user;', $processor->exportSearchCondition('filter_query'));
    }

    public function testProcessSearchCodeFromPost()
    {
        // Execute the real processor
        $this->processor->processRequest(Argument::any())->willReturn($this->processor)->shouldBeCalled();
        $this->processor->isValid()->willReturn(true)->shouldBeCalled();
        $this->processor->getSearchCondition()->willReturn($this->condition);
        $this->processor->getSearchCode()->willReturn('bmFtZTogeW9kYTs');

        // Store cached condition
        $this->cache->save('search_condition.test.bmFtZTogeW9kYTs', Argument::any())->shouldBeCalled();

        // Exporter
        $this->cache->contains('search_export.bmFtZTogeW9kYTs.filter_query')->willReturn(false);
        $this->cache->fetch('search_export.bmFtZTogeW9kYTs.formats')->willReturn(false);

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list', 'POST', ['rollerworks_search' => ['filter' => 'name: yoda;']]));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogeW9kYTs', $processor->getSearchCode());
    }

    public function testProcessSearchCodeFromPostClearsCache()
    {
        // Execute the real processor
        $this->processor->processRequest(Argument::any())->willReturn($this->processor)->shouldBeCalled();
        $this->processor->isValid()->willReturn(true)->shouldBeCalled();
        $this->processor->getSearchCondition()->willReturn($this->condition);
        $this->processor->getSearchCode()->willReturn('bmFtZTogeW9kYTs');

        // Currently Exported
        //$this->cache->contains('search_export.bmFtZTogeW9kYTs.filter_query')->willReturn(false);
        $this->cache->fetch('search_export.bmFtZTogdXNlcjs.formats')->willReturn(['filter_query' => true, 'xml' => true]);

        // Store cached condition
        $this->cache->save('search_condition.test.bmFtZTogeW9kYTs', Argument::any())->shouldBeCalled();

        // Remove old
        $this->cache->delete('search_condition.test.bmFtZTogdXNlcjs')->shouldBeCalled();
        $this->cache->delete('search_export.test.bmFtZTogdXNlcjs.filter_query')->shouldBeCalled();
        $this->cache->delete('search_export.test.bmFtZTogdXNlcjs.xml')->shouldBeCalled();

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter=bmFtZTogdXNlcjs', 'POST', ['rollerworks_search' => ['filter' => 'name: yoda;']]));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogeW9kYTs', $processor->getSearchCode());
    }

    public function testProcessSearchCodeFromPostWithInvalidValues()
    {
        // Execute the real processor
        $this->processor->processRequest(Argument::any())->willReturn($this->processor)->shouldBeCalled();
        $this->processor->isValid()->willReturn(false)->shouldBeCalled();
        $this->processor->getErrors()->willReturn(['oops'])->shouldBeCalled();

        // Don't store invalid search condition
        $this->cache->save(Argument::any(), Argument::any())->shouldNotBeCalled();

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list', 'POST', ['rollerworks_search' => ['filter' => 'name: foo bar;']]));

        $this->assertFalse($processor->isValid());
        $this->assertEquals(['oops'], $processor->getErrors());
    }
}
