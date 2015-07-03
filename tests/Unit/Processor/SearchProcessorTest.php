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

use Prophecy\Argument;
use Rollerworks\Bundle\SearchBundle\Processor\SearchProcessor;
use Rollerworks\Component\Search\Exporter;
use Rollerworks\Component\Search\FieldAliasResolver\NoopAliasResolver;
use Rollerworks\Component\Search\FieldLabelResolver\NoopLabelResolver;
use Rollerworks\Component\Search\Input;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\UriEncoder\Encoder\Base64UriEncoder;
use Symfony\Component\HttpFoundation\Request;

final class SearchProcessorTest extends SearchIntegrationTestCase
{
    private $inputFactory;
    private $exportFactory;
    private $conditionOptimizer;
    private $translator;
    private $validator;

    /**
     * @var Base64UriEncoder
     */
    private $uirEncoder;

    protected function setUp()
    {
        parent::setUp();

        $this->inputFactory = $this->prophesize('Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\InputFactory');
        $this->inputFactory->create(Argument::any())->will(
            function ($args) {
                switch ($args[0]) {
                    case 'filter_query':
                        return new Input\FilterQueryInput(new NoopAliasResolver());

                    case 'xml':
                        return new Input\XmlInput(new NoopAliasResolver());

                    default:
                        throw new \InvalidArgumentException(
                            sprintf('%s is not supported for this test case', $args[0])
                        );
                }
            }
        );

        $this->exportFactory = $this->prophesize('Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ExporterFactory');
        $this->exportFactory->create(Argument::any())->will(
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

        $this->translator = $this->prophesize('Symfony\Component\Translation\Translator');
        $this->translator->trans(Argument::any())->willReturnArgument(0);

        $this->conditionOptimizer = $this->prophesize('Rollerworks\Component\Search\SearchConditionOptimizerInterface');
        $this->validator = $this->prophesize('Rollerworks\Component\Search\Extension\Symfony\Validator\Validator');
        $this->uirEncoder = new Base64UriEncoder();
    }

    private function createProcessor($uriPrefix = '', $formFieldPattern = 'rollerworks_search[%s]')
    {
        $config = new Input\ProcessorConfig($this->getFieldSet(true));
        $processor = new SearchProcessor(
            $this->inputFactory->reveal(),
            $this->exportFactory->reveal(),
            $this->conditionOptimizer->reveal(),
            $this->validator->reveal(),
            $this->translator->reveal(),
            $this->uirEncoder,
            $config,
            $uriPrefix,
            $formFieldPattern
        );

        return $processor;
    }

    public function testProcessingEmptyRequestIsValid()
    {
        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list'));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('', $processor->getSearchCode());

        // This should be 'empty' as the filter doesn't apply for the processor
        $processor2 = $this->createProcessor('auth');
        $processor2->processRequest(Request::create('/list?filter=foo'));

        $this->assertTrue($processor2->isValid());
        $this->assertEquals('', $processor2->getSearchCode());
    }

    public function testProcessingFilterWithNoneScalarIsEmpty()
    {
        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter[foo]=bar'));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('', $processor->getSearchCode());
    }

    public function testProcessSearchCodeFromQuery()
    {
        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter='.$this->uirEncoder->encodeUri('name: user;')));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
    }

    public function testProcessSearchCodeFromPost()
    {
        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list', 'POST', ['rollerworks_search' => ['filter' => 'name: user;']]));

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
    }

    public function testProcessSearchCodeFromPostWithExisting()
    {
        $processor = $this->createProcessor();
        $processor->processRequest(
            Request::create(
                '/list?filter='.$this->uirEncoder->encodeUri('name: user2;'),
                'POST',
                ['rollerworks_search' => ['filter' => 'name: user;']]
            )
        )
        ;

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());
    }

    public function testProcessMultipleSearchCodesFromQuery()
    {
        $request = Request::create('/list?user[filter]='.$this->uirEncoder->encodeUri('name: user;').'&auth[filter]='.$this->uirEncoder->encodeUri('name: yoda;'));

        $processor = $this->createProcessor('user');
        $processor->processRequest($request);

        $this->assertTrue($processor->isValid());
        $this->assertEquals('bmFtZTogdXNlcjs', $processor->getSearchCode());

        $processor2 = $this->createProcessor('auth');
        $processor2->processRequest($request);

        $this->assertTrue($processor2->isValid());
        $this->assertEquals('bmFtZTogeW9kYTs', $processor2->getSearchCode());
    }

    public function testSearchCodeWithSyntaxErrorIsInvalid()
    {
        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter='.$this->uirEncoder->encodeUri('name: foo bar;')));

        $this->assertFalse($processor->isValid());
        $this->assertCount(1, $processor->getErrors());
    }

    public function testSearchCodeWithValidationErrorsIsInvalid()
    {
        $this->validator->validate(Argument::any())->will(
            function ($arg) {
                /** @var SearchConditionInterface $condition */
                $condition = $arg[0];
                $condition->getValuesGroup()->getField('name')->addError(
                    new ValuesError('singleValues[0].value', 'This ain\'t no crow bar!')
                );

                $condition->getValuesGroup()->getGroup(0)->getField('name')->addError(
                    new ValuesError('singleValues[0].value', 'What "bar"?')
                );

                return false;
            }
        );

        $processor = $this->createProcessor();
        $processor->processRequest(Request::create('/list?filter='.$this->uirEncoder->encodeUri('name: "crow bar"; ( name: bar; )')));

        $this->assertFalse($processor->isValid());
        $this->assertCount(1, $processor->getErrors());
    }
}
