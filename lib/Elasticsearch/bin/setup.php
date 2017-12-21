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

require __DIR__.'/../vendor/autoload.php';

$elasticaClient = new \Elastica\Client();

$elasticaIndex = $elasticaClient->getIndex('twitter');
$elasticaIndex->create(
    [
        'number_of_shards' => 4,
        'number_of_replicas' => 1,
        'analysis' => [
            'analyzer' => [
                'default' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => ['lowercase', 'mySnowball'],
                ],
                'default_search' => [
                    'type' => 'custom',
                    'tokenizer' => 'standard',
                    'filter' => ['standard', 'lowercase', 'mySnowball'],
                ],
            ],
            'filter' => [
                'mySnowball' => [
                    'type' => 'snowball',
                    'language' => 'English',
                ],
            ],
        ],
    ],
    true
);

//Create a type
$elasticaType = $elasticaIndex->getType('tweet');

// Define mapping
$mapping = new \Elastica\Type\Mapping();
$mapping->setType($elasticaType);

// Set mapping
$mapping->setProperties(
    [
        'id' => ['type' => 'integer', 'include_in_all' => false],
        'user' => [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'text', 'include_in_all' => true],
                'fullName' => ['type' => 'text', 'include_in_all' => true, 'boost' => 2],
            ],
        ],
        'msg' => ['type' => 'text', 'include_in_all' => true],
        'tstamp' => ['type' => 'date', 'include_in_all' => false],
        'location' => ['type' => 'geo_point', 'include_in_all' => false],
    ]
);

// Send mapping to type
$mapping->send();

////////////////////
////////////////////

// The Id of the document
$id = 1;

// Create a document
$tweet = [
    'id' => $id,
    'user' => [
        'name' => 'mewantcookie',
        'fullName' => 'Cookie Monster',
    ],
    'msg' => 'Me wish there were expression for cookies like there is for apples. "A cookie a day make the doctor diagnose you with diabetes" not catchy.',
    'tstamp' => '1238081389',
    'location' => '41.12,-71.34',
];

// First parameter is the id of document.
$tweetDocument = new \Elastica\Document($id, $tweet);

// Add tweet to type
$elasticaType->addDocument($tweetDocument);

// Refresh Index
$elasticaType->getIndex()->refresh();

//$documents = [];
//while ( ... ) { // Fetching content from the database
//    $documents[] = new \Elastica\Document(
//        $id,
//        array(
//            ...
//        );
//    );
//}
//$elasticaType->addDocuments($documents);
//$elasticaType->getIndex()->refresh();
