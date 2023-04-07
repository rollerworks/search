<?php

// See https://www.park-manager.com/hubkit/config.html

return [
    'schema_version' => 2,
    'branches' => [
        ':default' => [
            'sync-tags' => true,
            'split' => [
                'lib/ApiPlatform' => 'git@github.com:rollerworks/search-api-platform.git',
                'lib/Core' => 'git@github.com:rollerworks/search-core.git',
                'lib/Doctrine/Dbal' => 'git@github.com:rollerworks/search-doctrine-dbal.git',
                'lib/Doctrine/Orm' => 'git@github.com:rollerworks/search-doctrine-orm.git',
                'lib/Elasticsearch' => 'git@github.com:rollerworks/search-elasticsearch.git',
                'lib/Symfony/SearchBundle' => 'git@github.com:rollerworks/RollerworksSearchBundle.git',
                'lib/Symfony/Validator' => 'git@github.com:rollerworks/search-symfony-validator.git',
            ],
        ],
    ],
];
