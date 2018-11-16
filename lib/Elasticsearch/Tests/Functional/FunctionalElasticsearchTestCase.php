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

use Elastica\Client;
use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Search;
use Elastica\Type\Mapping;
use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\ChoiceType;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Elasticsearch\ElasticsearchTestCase;

/**
 * Class FunctionalElasticsearchTestCase.
 */
abstract class FunctionalElasticsearchTestCase extends ElasticsearchTestCase
{
    protected function setUp()
    {
        $mappings = $this->getMappings();
        $documents = $this->getDocuments();

        foreach ($mappings as $name => $properties) {
            if (false === array_key_exists($name, $documents)) {
                throw new \RuntimeException(sprintf('No documents for mapping "%1$s" defined', $name));
            }
            $data = $documents[$name];
            $this->createDocuments($name, $properties, $data);
        }

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getMappings(): array
    {
        return [
            'customers' => [
                    'type' => ['type' => 'join', 'relations' => ['customer' => 'note']],
                    'first_name' => ['type' => 'text'],
                    'last_name' => ['type' => 'text'],
                    'full_name' => ['type' => 'text', 'boost' => 2, 'index' => 'not_analyzed'],
                    'birthday' => ['type' => 'date'],
                    'reg_date' => ['type' => 'date'],
                    'comment' => ['type' => 'text'],
                ],
            'invoices' => [
                    'customer' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'full_name' => ['type' => 'text', 'index' => 'not_analyzed'],
                            'birthday' => ['type' => 'date'],
                        ],
                    ],
                    'label' => ['type' => 'string'],
                    'pubdate' => ['type' => 'date'],
                    'pubdatetime' => ['type' => 'date'],
                    'status' => ['type' => 'integer'],
                    'price_total' => ['type' => 'integer'],
                    'items' => [
                        'type' => 'nested',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer'],
                            'price' => ['type' => 'integer'],
                            'total' => ['type' => 'integer'],
                        ],
                    ],
                ],
        ];
    }

    /**
     * @return array
     */
    protected function getDocuments(): array
    {
        return [
            'customers' => [
                // customers
                1 => ['customer', 'Peter', 'Pang', 'Peter Pang', '1980-11-20', '2005-11-20', null],
                2 => ['customer', 'Leroy', 'Jenkins', 'Leroy Jenkins', '2000-05-15', '2005-05-20', null],
                3 => ['customer', 'Doctor', 'Who', 'Doctor Who', '2005-12-10', '2005-02-20', null],
                4 => ['customer', 'Spider', 'Pig', 'Spider Pig', '2005-12-10', '2012-07-20', null],

                // notes about customers
                5 => [['name' => 'note', 'parent' => 2], null, null, null, null, null, 'Leeroy Jenkins!'],
                6 => [['name' => 'note', 'parent' => 3], null, null, null, null, null, 'Que?'],
                7 => [['name' => 'note', 'parent' => 3], null, null, null, null, null, 'Who?'],
                8 => [['name' => 'note', 'parent' => 4], null, null, null, null, null, 'Spider Pig, Spider Pig, Does Whatever A Spider Pig Does'],
            ],
            'invoices' => [
                1 => [['id' => 1, 'full_name' => 'Peter Pang', 'birthday' => '1980-11-20'], '2010-001', '2010-05-10', '2010-05-10T01:12:13+00:00', 2, 10000, [
                    ['label' => 'Electric Guitar', 'quantity' => 1, 'price' => 10000, 'total' => 10000],
                ]],
                2 => [['id' => 2, 'full_name' => 'Leroy Jenkins', 'birthday' => '2000-05-15'], '2010-002', '2010-05-10', '2010-05-10T01:12:13+00:00', 2, 9000, [
                    ['label' => 'Sword', 'quantity' => 1, 'price' => 1500, 'total' => 1500],
                    ['label' => 'Shield', 'quantity' => 1, 'price' => 2000, 'total' => 2000],
                    ['label' => 'Armor', 'quantity' => 1, 'price' => 5500, 'total' => 5500],
                ]],
                3 => [['id' => 2, 'full_name' => 'Leroy Jenkins', 'birthday' => '2000-05-15'], null, null, null, 0, 1000, [
                    ['label' => 'Sword', 'quantity' => 1, 'price' => 1000, 'total' => 1000],
                ]],
                4 => [['id' => 2, 'full_name' => 'Leroy Jenkins', 'birthday' => '2000-05-15'], '2015-001', '2015-05-10', '2015-05-10T01:12:13+00:00', 1, 10000, [
                    ['label' => 'Armor repair kit', 'quantity' => 2, 'price' => 5000, 'total' => 10000],
                ]],
                5 => [['id' => 3, 'full_name' => 'Doctor Who', 'birthday' => '2005-12-10'], '2015-002', '2015-05-01', '2015-05-01T01:12:13+00:00', 1, 21500, [
                    ['label' => 'TARDIS Chameleon circuit', 'quantity' => 1, 'price' => 1500, 'total' => 1500],
                    ['label' => 'Sonic Screwdriver', 'quantity' => 10, 'price' => 2000, 'total' => 20000],
                ]],
                6 => [['id' => 4, 'full_name' => 'Spider Pig', 'birthday' => '2005-12-10'], '2015-003', '2015-05-05', '2015-05-05T01:12:13+00:00', 1, 6000, [
                    ['label' => 'Web shooter', 'quantity' => 1, 'price' => 1000, 'total' => 1000],
                    ['label' => 'Cape', 'quantity' => 1, 'price' => 1000, 'total' => 1000],
                    ['label' => 'Cape repair manual', 'quantity' => 1, 'price' => 1000, 'total' => 1000],
                    ['label' => 'Hoof polish', 'quantity' => 3, 'price' => 1000, 'total' => 3000],
                ]],
            ],
        ];
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        // TODO: extract settings to config file, add proper logger
        return new Client([
            'host' => getenv('ELASTICSEARCH_HOST') ?: 'elasticsearch',
            'port' => getenv('ELASTICSEARCH_PORT') ?: 9200,
        ]);
    }

    /**
     * @param string $name
     * @param array  $properties
     * @param array  $data
     */
    protected function createDocuments(string $name, array $properties, array $data)
    {
        // index
        $client = $this->getClient();
        $index = $client->getIndex($name);
        $index->create([
            'mapping.single_type' => true,
        ], ['recreate' => true]);

        // mapping
        $type = $index->getType($name);
        $mapping = new Mapping($type, $properties);
        $mapping->send();

        // documents
        if (false === empty($data)) {
            $documents = [];
            foreach ($data as $id => $item) {
                $normalized = array_combine(array_keys($properties), $item);
                $document = new Document($id, $normalized);

                if (isset($normalized['type']['parent'])) {
                    $document->setRouting($normalized['type']['parent']);
                }
                $documents[] = $document;
            }
            $type->addDocuments($documents);
            $index->refresh();
        }
    }

    /**
     * @param bool $build
     *
     * @return FieldSet
     */
    protected function getFieldSet(bool $build = true): FieldSet
    {
        $builder = $this->getFactory()->createFieldSetBuilder();

        // Customer (by invoice relation)
        $builder->add('customer-first-name', TextType::class);
        $builder->add('customer-last-name', TextType::class);
        $builder->add('customer-name', TextType::class);
        $builder->add('customer-birthday', BirthdayType::class, ['pattern' => 'yyyy-MM-dd']);
        $builder->add('customer-comment', TextType::class);

        // Invoice
        $builder->add('id', IntegerType::class);
        $builder->add('customer', IntegerType::class);
        $builder->add('label', TextType::class);
        $builder->add('pub-date', DateType::class, ['pattern' => 'yyyy-MM-dd']);
        $builder->add('pub-date-time', DateTimeType::class, ['pattern' => 'yyyy-MM-dd HH:mm:ss']);
        $builder->add('status', ChoiceType::class, ['choices' => ['concept' => 0, 'published' => 1, 'paid' => 2]]);
        $builder->add('total', MoneyType::class);

        // Invoice Details
        $builder->add('row-label', TextType::class);
        $builder->add('row-quantity', IntegerType::class);
        $builder->add('row-price', MoneyType::class);
        $builder->add('row-total', MoneyType::class);

        return $builder->getFieldSet('invoice');
    }

    /**
     * @param QueryConditionGenerator $conditionGenerator
     */
    protected function configureConditionGenerator(QueryConditionGenerator $conditionGenerator)
    {
        // customer
        $conditionGenerator->registerField('customer-comment', 'customers/customers/#note>comment');

        // invoice
        $conditionGenerator->registerField('id', 'invoices/invoices#_id');
        $conditionGenerator->registerField('pub-date', 'invoices/invoices#pubdate');
        $conditionGenerator->registerField('pub-date-time', 'invoices/invoices#pubdatetime');
        $conditionGenerator->registerField('label', '/invoices/invoices#label');
        $conditionGenerator->registerField('status', '/invoices/invoices#status');
        $conditionGenerator->registerField('total', '/invoices/invoices#price_total');

        // invoice.customer
        $conditionGenerator->registerField('customer', 'invoices/invoices#customer.id');
        $conditionGenerator->registerField('customer-name', 'invoices/invoices#customer.full_name');
        $conditionGenerator->registerField('customer-birthday', 'invoices/invoices#customer.birthday');

        // invoice.item[]
        $conditionGenerator->registerField('row-label', 'invoices/invoices#items[].label');
        $conditionGenerator->registerField('row-price', 'invoices/invoices#items[].price');
        $conditionGenerator->registerField('row-quantity', 'invoices/invoices#items[].quantity');
        $conditionGenerator->registerField('row-total', 'invoices/invoices#items[].total');
    }

    /**
     * @param SearchCondition $condition
     * @param int[]           $expectedIds
     */
    protected function assertDocumentsAreFound(SearchCondition $condition, array $expectedIds)
    {
        $conditionGenerator = new QueryConditionGenerator($condition);
        $this->configureConditionGenerator($conditionGenerator);

        $mappings = $conditionGenerator->getMappings();
        $query = $conditionGenerator->getQuery();

        $search = new Search($this->getClient());
        foreach ($mappings as $mapping) {
            $search
                ->addIndex($mapping->indexName)
                ->addType($mapping->typeName);
        }

        try {
            $results = $search->search($query);
            $documents = $results->getDocuments();
            $foundIds = array_map(
                function (Document $document) {
                    return $document->getId();
                },
                $documents
            );
        } catch (ResponseException $exception) {
            $this->fail(sprintf(
                "%s\nWith path: %s\nWith query: ---------------------\n%s\n---------------------------------\n",
                $exception->getMessage(),
                $search->getPath(),
                json_encode($query, JSON_PRETTY_PRINT)
            ));

            return;
        }

        // TODO: this sort shouldn't be necessary, order is not arbitrary for a search result
        sort($expectedIds);
        sort($foundIds);

        $this->assertEquals(
            $expectedIds,
            $foundIds,
            sprintf(
                "Found these records instead: \n%s\n"
                ."With query: ---------------------\n%s\n---------------------------------\n",
                print_r($documents, true),
                json_encode($query, JSON_PRETTY_PRINT)
            )
        );
    }
}
