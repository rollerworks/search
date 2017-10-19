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
                    'first_name' => ['type' => 'text'],
                    'last_name' => ['type' => 'text'],
                    'full_name' => ['type' => 'text', 'boost' => 2],
                    'birthday' => ['type' => 'date'],
                    'reg_date' => ['type' => 'date'],
                ],
            'invoices' => [
                    'customer' => [
                        'type' => 'object',
                        'properties' => [
                            'full_name' => ['type' => 'text'],
                        ],
                    ],
                    'label' => ['type' => 'string'],
                    'pubdate' => ['type' => 'date'],
                    'status' => ['type' => 'integer'],
                    'price_total' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                    'items' => [
                        'type' => 'nested',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'quantity' => ['type' => 'integer'],
                            'price' => ['type' => 'scaled_float', 'scaling_factor' => 100],
                            'total' => ['type' => 'scaled_float', 'scaling_factor' => 100],
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
                1 => ['Peter', 'Pang', 'Peter Pang', '1980-11-20', '2005-11-20'],
                2 => ['Leroy', 'Jenkins', 'Leroy Jenkins', '2000-05-15', '2005-05-20'],
                3 => ['Doctor', 'Who', 'Doctor Who', '2005-12-10', '2005-02-20'],
                4 => ['Spider', 'Pig', 'Spider Pig', '2012-06-10', '2012-07-20'],
            ],
            'invoices' => [
                1 => [['full_name' => 'Peter Pang'], '2010-001', '2010-05-10', 2, 100.00, [
                    ['label' => 'Electric Guitar', 'quantity' => 1, 'price' => 100.00, 'total' => 100.00],
                ]],
                2 => [['full_name' => 'Leroy Jenkins'], '2010-002', '2010-05-10', 2, 90.00, [
                    ['label' => 'Sword', 'quantity' => 1, 'price' => 15.00, 'total' => 15.00],
                    ['label' => 'Shield', 'quantity' => 1, 'price' => 20.00, 'total' => 20.00],
                    ['label' => 'Armor', 'quantity' => 1, 'price' => 55.00, 'total' => 55.00],
                ]],
                3 => [['full_name' => 'Leroy Jenkins'], null, null, 0, 10.00, [
                    ['label' => 'Sword', 'quantity' => 1, 'price' => 10.00, 'total' => 10.00],
                ]],
                4 => [['full_name' => 'Leroy Jenkins'], '2015-001', '2015-05-10', 1, 100.00, [
                    ['label' => 'Armor repair kit', 'quantity' => 2, 'price' => 50.00, 'total' => 100.00],
                ]],
                5 => [['full_name' => 'Doctor Who'], '2015-002', '2015-05-01', 1, 215.00, [
                    ['label' => 'TARDIS Chameleon circuit', 'quantity' => 1, 'price' => 15.00, 'total' => 15.00],
                    ['label' => 'Sonic Screwdriver', 'quantity' => 10, 'price' => 20.00, 'total' => 200.00],
                ]],
                6 => [['full_name' => 'Spider Pig'], '2015-003', '2015-05-05', 1, 60.00, [
                    ['label' => 'Web shooter', 'quantity' => 1, 'price' => 10.00, 'total' => 10.00],
                    ['label' => 'Cape', 'quantity' => 1, 'price' => 10.00, 'total' => 10.00],
                    ['label' => 'Cape repair manual', 'quantity' => 1, 'price' => 10.00, 'total' => 10.00],
                    ['label' => 'Hoof polish', 'quantity' => 3, 'price' => 10.00, 'total' => 30.00],
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
        return new Client();
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
        $index->create([], ['recreate' => true]);

        // mapping
        $type = $index->getType($name);
        $mapping = new Mapping($type, $properties);
        $mapping->send();

        // documents
        if (false === empty($data)) {
            $documents = [];
            foreach ($data as $id => $document) {
                $documents[] = new Document($id, array_combine(array_keys($properties), $document));
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

        // Invoice
        $builder->add('id', IntegerType::class);
        $builder->add('customer', IntegerType::class);
        $builder->add('label', TextType::class);
        $builder->add('pub-date', DateType::class, ['pattern' => 'yyyy-MM-dd']);
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
        $conditionGenerator->registerField('id', '/invoices/invoices#_id');

        // TODO: fill these out properly
        $conditionGenerator->registerField('label', '/invoices/invoices#label');
        $conditionGenerator->registerField('pub-date', '/invoices/invoices#pubdate');
        $conditionGenerator->registerField('status', '/invoices/invoices#status');
        $conditionGenerator->registerField('total', '/invoices/invoices#total');

        // $conditionGenerator->registerField('status', 'status');
        // $conditionGenerator->registerField('total', 'total');
        // $conditionGenerator->registerField('row-label', 'label');
        // $conditionGenerator->registerField('row-price', 'price');
        // $conditionGenerator->registerField('row-quantity', 'quantity');
        // $conditionGenerator->registerField('row-total', 'total');
        // $conditionGenerator->registerField('customer', 'id');
        // $conditionGenerator->registerField('customer-name#first_name', 'firstName');
        // $conditionGenerator->registerField('customer-name#last_name', 'lastName');
        // $conditionGenerator->registerField('customer-birthday', 'birthday');
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
