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

namespace Rollerworks\Component\Search\Processor\Tests;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\Psr7SearchProcessor;
use Rollerworks\Component\Search\Processor\Tests\Fixtures\FieldSet\UserFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\UriEncoder\Encoder\Base64UriEncoder;
use Zend\Diactoros\ServerRequest;

final class GenericSearchProcessorTest extends SearchIntegrationTestCase
{
    /**
     * @var Base64UriEncoder
     */
    private $uriEncoder;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @var SearchCondition
     */
    private $condition;

    protected function setUp()
    {
        parent::setUp();

        $this->uriEncoder = new Base64UriEncoder();
        $this->fieldSet = $this->getFactory()->createFieldSet(UserFieldSet::class);
        $this->condition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('user'))
        );
    }

    private function createProcessor()
    {
        $processor = new Psr7SearchProcessor(
            $this->getFactory(),
            InputProcessorLoader::create(),
            ConditionExporterLoader::create(),
            $this->uriEncoder
        );

        return $processor;
    }

    public function testProcessingEmptyRequestIsValid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = new ServerRequest([], [], '/list', 'GET');

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertNull($payload->searchCondition);
        self::assertNull($payload->exportedFormat);
        self::assertEmpty($payload->searchCode);
    }

    public function testProcessSearchCodeFromQuery()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => 'eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQ~string_query']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('string_query', $payload->exportedFormat);
        self::assertEquals('name: user;', $payload->exportedCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQ~string_query', $payload->searchCode);
    }

    public function testProcessSearchCodeFromPost()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list', 'POST'))->withParsedBody(['search' => 'name: user;']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('name: user;', $payload->exportedCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQ~string_query', $payload->searchCode);
    }

    public function testProcessSearchCodeFromPostWithCustomFormat()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list', 'POST'))->withParsedBody(['search' => '{"fields":{"name":{"simple-values":["user"]}}}', 'format' => 'json']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('json', $payload->exportedFormat);
        self::assertEquals('{"fields":{"name":{"simple-values":["user"]}}}', $payload->exportedCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQ~json', $payload->searchCode);
    }

    public function testProcessSearchCodeFromPostWithExisting()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list', 'POST'))
            ->withQueryParams(['search' => 'eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQo~string_query'])
            ->withParsedBody(['search' => 'name: yoda;']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        $expectedCondition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('yoda'))
        );

        self::assertTrue($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals('string_query', $payload->exportedFormat);
        self::assertEquals('name: yoda;', $payload->exportedCondition);
        self::assertEquals($expectedCondition, $payload->searchCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInlvZGEiXX19fQ~string_query', $payload->searchCode);
    }

    public function testProcessMultipleSearchCodesFromQuery()
    {
        $request = (new ServerRequest([], [], '/list'))
            ->withQueryParams(
                [
                    'user' => ['search' => 'eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQo~string_query'],
                    'auth' => ['search' => 'eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInlvZGEiXX19fQ~string_query'],
                ]
            );

        $config1 = (new ProcessorConfig($this->fieldSet))->setRequestPrefix('user');
        $config2 = (new ProcessorConfig($this->fieldSet))->setRequestPrefix('auth');

        $payload1 = $this->createProcessor()->processRequest($request, $config1);
        $payload2 = $this->createProcessor()->processRequest($request, $config2);

        $expectedCondition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('user'))
        );

        self::assertFalse($payload1->isChanged());
        self::assertTrue($payload1->isValid());
        self::assertEmpty($payload1->messages);
        self::assertEquals($expectedCondition, $payload1->searchCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQ~string_query', $payload1->searchCode);

        $expectedCondition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('yoda'))
        );

        self::assertFalse($payload2->isChanged());
        self::assertTrue($payload2->isValid());
        self::assertEmpty($payload2->messages);
        self::assertEquals($expectedCondition, $payload2->searchCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInlvZGEiXX19fQ~string_query', $payload2->searchCode);
    }

    public function testProcessSearchCodeFromPostWithSecondary()
    {
        // NOTE: This test is to ensure submitting two conditions works as expected.
        // In actual usage you would only process the changed condition
        // (detected by which button is pushed or such).

        $request = (new ServerRequest([], [], '/list', 'POST'))
            ->withQueryParams(
                [
                    'user' => ['search' => 'eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInVzZXIiXX19fQo~string_query'],
                    'auth' => ['search' => 'eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInlvZGEiXX19fQ~string_query'],
                ]
            )
            ->withParsedBody(['user' => ['search' => 'name: doctor;'], 'auth' => ['search' => 'name: yoda;']]);

        $config1 = (new ProcessorConfig($this->fieldSet))->setRequestPrefix('user');
        $config2 = (new ProcessorConfig($this->fieldSet))->setRequestPrefix('auth');

        $payload1 = $this->createProcessor()->processRequest($request, $config1);
        $payload2 = $this->createProcessor()->processRequest($request, $config2);

        $expectedCondition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('doctor'))
        );

        self::assertTrue($payload1->isChanged());
        self::assertTrue($payload1->isValid());
        self::assertEmpty($payload1->messages);
        self::assertEquals($expectedCondition, $payload1->searchCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbImRvY3RvciJdfX19~string_query', $payload1->searchCode);

        $expectedCondition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('yoda'))
        );

        self::assertTrue($payload2->isChanged());
        self::assertTrue($payload2->isValid());
        self::assertEmpty($payload2->messages);
        self::assertEquals($expectedCondition, $payload2->searchCondition);
        self::assertEquals('eyJmaWVsZHMiOnsibmFtZSI6eyJzaW1wbGUtdmFsdWVzIjpbInlvZGEiXX19fQ~string_query', $payload2->searchCode);
    }

    public function testSearchCodeWithBrokenSearchCodeIsInvalid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => 'eyJmaWVsZHMiOnsibmFtZ~string_query']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isValid());
        self::assertEquals([ConditionErrorMessage::withMessageTemplate('', 'Invalid search code, check if the URL was truncated.')], $payload->messages);
        self::assertNull($payload->searchCondition);
        self::assertEquals('', $payload->exportedCondition);
        self::assertEquals('', $payload->searchCode);
    }

    public function testSearchCodeWithSyntaxErrorIsInvalid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => $this->uriEncoder->encodeUri('{"fields":{"name":{"simple-values":[[]]}}}')]);

        $payload = $this->createProcessor()->processRequest($request, $config);
        $payload->messages[0]->cause = null;

        self::assertFalse($payload->isValid());
        self::assertEquals([ConditionErrorMessage::withMessageTemplate('[fields][name][simple-values][0]', 'This value is not valid.')], $payload->messages);
        self::assertNull($payload->searchCondition);
        self::assertEquals('', $payload->exportedCondition);
        self::assertEquals('', $payload->searchCode);
    }
}
