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
use Rollerworks\Component\Search\Field\OrderFieldType;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Tests\Elasticsearch\ElasticsearchTestCase;

/**
 * Class FunctionalElasticsearchTestCase.
 */
abstract class FunctionalElasticsearchTestCase extends ElasticsearchTestCase
{
    protected function setUp(): void
    {
        $mappings = $this->getMappings();
        $documents = $this->getDocuments();

        foreach ($mappings as $name => $properties) {
            if (false === \array_key_exists($name, $documents)) {
                throw new \RuntimeException(sprintf('No documents for mapping "%1$s" defined', $name));
            }
            $data = $documents[$name];
            $this->createDocuments($name, $properties, $data);
        }

        parent::setUp();
    }

    protected function getMappings(): array
    {
        return [
            'customers' => [
                    'type' => ['type' => 'join', 'relations' => ['customer' => 'note']],
                    'first_name' => ['type' => 'text'],
                    'last_name' => ['type' => 'text'],
                    'full_name' => ['type' => 'text', 'boost' => 2, 'fields' => ['keyword' => ['type' => 'keyword']]],
                    'birthday' => ['type' => 'date'],
                    'pubdate' => ['type' => 'date'],
                    'comment' => ['type' => 'text'],
                ],
            'invoices' => [
                    'customer' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'integer'],
                            'full_name' => ['type' => 'text', 'fields' => ['keyword' => ['type' => 'keyword']]],
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

    protected function getDocuments(): array
    {
        $date = static function (string $input) {
            return (new \DateTimeImmutable($input, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:sP');
        };

        return [
            'customers' => [
                // customers
                1 => ['customer', 'Peter', 'Pang', 'Peter Pang', '1980-11-20', '2005-11-20', null],
                2 => ['customer', 'Leroy', 'Jenkins', 'Leroy Jenkins', '2000-05-15', '2005-05-20', null],
                3 => ['customer', 'Doctor', 'Who', 'Doctor Who', '2005-12-10', '2005-02-20', null],
                4 => ['customer', 'Spider', 'Pig', 'Spider Pig', '2005-12-10', '2012-07-20', null],

                // notes about customers
                5 => [['name' => 'note', 'parent' => 2], null, null, null, null, '2010-11-10', 'Leeroy Jenkins!'],
                6 => [['name' => 'note', 'parent' => 3], null, null, null, null, '2010-11-10', 'Que?'],
                7 => [['name' => 'note', 'parent' => 3], null, null, null, null, '2010-11-13', 'Who?'],
                8 => [['name' => 'note', 'parent' => 4], null, null, null, null, '2005-01-01', 'Spider Pig, Spider Pig, Does Whatever A Spider Pig Does'],
                9 => [['name' => 'note', 'parent' => 1], 'Larry', null, null, null, '2018-12-31', 'Specific comment'],
                10 => [['name' => 'note', 'parent' => 2], 'Moe', null, null, null, '2015-11-10', 'Specific comment'],
                11 => [['name' => 'note', 'parent' => 3], 'Curly', null, null, null, '2013-11-10', 'Specific comment'],
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
                7 => [['id' => 1, 'full_name' => 'Peter Pang', 'birthday' => '1980-11-20'], '2019-001', '2015-05-10', $date('-7 days'), 3, 8000, [
                    ['label' => 'Air Guitar', 'quantity' => 1, 'price' => 8, 'total' => 8],
                ]],
                8 => [['id' => 1, 'full_name' => 'Peter Pang', 'birthday' => '1980-11-20'], '2020-019', '2015-05-10', $date('+15 days'), 3, 8000, [
                    ['label' => 'Air Guitar', 'quantity' => 1, 'price' => 8, 'total' => 8],
                ]],
                9 => [['id' => 1, 'full_name' => 'Peter Pang', 'birthday' => '1980-11-20'], '2021-005', '2015-05-10', $date('+1 year'), 3, 8000, [
                    ['label' => 'Air Guitar', 'quantity' => 1, 'price' => 8, 'total' => 8],
                ]],
            ],
        ];
    }

    protected function getClient(): Client
    {
        // TODO: extract settings to config file, add proper logger
        return new Client([
            'host' => getenv('ELASTICSEARCH_HOST') ?: 'elasticsearch',
            'port' => getenv('ELASTICSEARCH_PORT') ?: 9200,
        ]);
    }

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

    protected function getFieldSet(bool $build = true): FieldSet
    {
        $builder = $this->getFactory()->createFieldSetBuilder();

        // Customer (by invoice relation)
        $builder->add('customer-first-name', TextType::class);
        $builder->add('customer-last-name', TextType::class);
        $builder->add('customer-name', TextType::class);
        $builder->add('@customer-name', OrderFieldType::class);
        $builder->add('customer-birthday', BirthdayType::class, ['pattern' => 'yyyy-MM-dd']);
        $builder->add('@customer-birthday', OrderFieldType::class);
        $builder->add('@customer-pubdate', OrderFieldType::class);
        $builder->add('@customer-note-pubdate', OrderFieldType::class);
        $builder->add('customer-comment', TextType::class);
        $builder->add('customer-comment-restricted', TextType::class);

        // Invoice
        $builder->add('id', IntegerType::class);
        $builder->add('@id', OrderFieldType::class, ['default' => 'ASC']);
        $builder->add('customer', IntegerType::class);
        $builder->add('label', TextType::class);
        $builder->add('pub-date', DateType::class, ['pattern' => 'yyyy-MM-dd']);
        $builder->add('@pub-date', OrderFieldType::class);
        $builder->add('pub-date-time', DateTimeType::class, ['pattern' => 'yyyy-MM-dd HH:mm:ss', 'allow_relative' => true]);
        $builder->add('status', ChoiceType::class, ['choices' => ['concept' => 0, 'published' => 1, 'paid' => 2, 'overdue' => 3]]);
        $builder->add('total', MoneyType::class);
        $builder->add('@total', OrderFieldType::class);

        // Invoice Details
        $builder->add('row-label', TextType::class);
        $builder->add('row-quantity', IntegerType::class);
        $builder->add('row-price', MoneyType::class);
        $builder->add('row-total', MoneyType::class);

        return $builder->getFieldSet('invoice');
    }

    protected function configureConditionGenerator(QueryConditionGenerator $conditionGenerator)
    {
        // customer
        $conditionGenerator->registerField('customer-comment', 'customers/customers/#note>comment');
        $conditionGenerator->registerField(
            'customer-comment-restricted',
            'customers/customers/#note>comment',
            [
                // restrict by note author's first name
                'customers/customers/#note>first_name' => 'moe',
            ]
        );

        $conditionGenerator->registerField('@customer-pubdate', '/customers/customers#pubdate', [
            '#type' => 'customer',
        ]);

        $conditionGenerator->registerField('@customer-note-pubdate', '/customers/customers#note>pubdate', [
            '#type' => 'customer',
        ]);

        // invoice
        $conditionGenerator->registerField('id', 'invoices/invoices#_id');
        $conditionGenerator->registerField('@id', 'invoices/invoices#_id');
        $conditionGenerator->registerField('pub-date', 'invoices/invoices#pubdate');
        $conditionGenerator->registerField('@pub-date', 'invoices/invoices#pubdate');
        $conditionGenerator->registerField('pub-date-time', 'invoices/invoices#pubdatetime');
        $conditionGenerator->registerField('label', '/invoices/invoices#label');
        $conditionGenerator->registerField('status', '/invoices/invoices#status');
        $conditionGenerator->registerField('total', '/invoices/invoices#price_total');
        $conditionGenerator->registerField('@total', '/invoices/invoices#price_total', [], ['price_total' => ['unmapped_type' => 'long']]);

        // invoice.customer
        $conditionGenerator->registerField('customer', 'invoices/invoices#customer.id');
        $conditionGenerator->registerField('customer-name', 'invoices/invoices#customer.full_name');
        $conditionGenerator->registerField('@customer-name', 'invoices/invoices#customer.full_name.keyword');
        $conditionGenerator->registerField('customer-birthday', 'invoices/invoices#customer.birthday');
        $conditionGenerator->registerField('@customer-birthday', 'invoices/invoices#customer.birthday');

        // invoice.item[]
        $conditionGenerator->registerField('row-label', 'invoices/invoices#items[].label');
        $conditionGenerator->registerField('row-price', 'invoices/invoices#items[].price');
        $conditionGenerator->registerField('row-quantity', 'invoices/invoices#items[].quantity');
        $conditionGenerator->registerField('row-total', 'invoices/invoices#items[].total');
    }

    /**
     * @param int[] $expectedIds
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
                json_encode($query->toArray(), JSON_PRETTY_PRINT)
            ));

            return;
        }

        $this->assertEquals(
            $expectedIds,
            $foundIds,
            sprintf(
                "Found these records instead: \n%s\n"
                ."With query: ---------------------\n%s\n---------------------------------\n",
                print_r($documents, true),
                json_encode($query->toArray(), JSON_PRETTY_PRINT)
            )
        );
    }
}
