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

final class SearchProcessorTest extends FunctionalTestCase
{
    public function testEmptySearchCodeIsValid()
    {
        $client = self::newClient(['config' => 'search_processor.yml']);

        $client->request('GET', '/search');

        $this->assertEquals('VALID: EMPTY', $client->getResponse()->getContent());
    }

    public function testPostCondition()
    {
        $client = self::newClient(['config' => 'search_processor.yml']);

        $client->request('POST', '/search', ['search' => 'name: user;']);
        $crawler = $client->followRedirect();

        self::assertEquals(
            'http://localhost/search?search=name:%20user;',
            $crawler->getUri()
        );

        self::assertEquals(
            'VALID: name: user;',
            $client->getResponse()->getContent()
        );
    }

    public function testInvalidConditionHasErrors()
    {
        $client = self::newClient(['config' => 'search_processor.yml']);

        $client->request('GET', '/search?search=first-name%3A%20user%3B');

        $this->assertEquals('INVALID: <ul><li>Field first-name is not registered in the FieldSet or available as alias.</li></ul>', $client->getResponse()->getContent());
    }
}
