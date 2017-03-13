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

use Rollerworks\Bundle\SearchBundle\Tests\Fixtures\FieldSet\UserFieldSet;
use Rollerworks\Component\Search\SearchFactory;

final class BootTest extends FunctionalTestCase
{
    /** @test */
    public function it_can_boot_the_application()
    {
        $client = self::newClient();
        $client->getKernel()->boot();

        self::assertInstanceOf(SearchFactory::class, $factory = $client->getContainer()->get('rollerworks_search.factory'));
        $factory->createFieldSet(UserFieldSet::class);
    }
}
