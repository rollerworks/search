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

namespace Rollerworks\Bundle\SearchBundle\Tests\Functional;

use Rollerworks\Component\Search\Elasticsearch\ElasticsearchFactory;

/**
 * @internal
 */
final class ElasticsearchTest extends FunctionalTestCase
{
    /** @test */
    public function elasticsearch_factory_is_accessible(): void
    {
        if (! class_exists(ElasticsearchFactory::class)) {
            self::markTestSkipped('rollerworks/search-elasticsearch is not installed');
        }

        $client = self::newClient(['config' => 'elasticsearch.yml']);
        $client->getKernel()->boot();

        $container = $client->getContainer();

        self::assertInstanceOf(ElasticsearchFactory::class, $container->get('rollerworks_search.elasticsearch.factory'));
    }
}
