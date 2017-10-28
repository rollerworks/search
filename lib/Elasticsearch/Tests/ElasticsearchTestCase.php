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

namespace Rollerworks\Component\Search\Tests\Elasticsearch;

use Rollerworks\Component\Search\Elasticsearch\Extension\ElasticsearchExtension;
use Rollerworks\Component\Search\SearchExtension;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

/**
 * Class ElasticsearchTestCase.
 */
abstract class ElasticsearchTestCase extends SearchIntegrationTestCase
{
    /**
     * @return SearchExtension[]
     */
    protected function getExtensions(): array
    {
        return [
            new ElasticsearchExtension(),
        ];
    }
}
