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

/**
 * @internal
 */
final class SearchProcessorTest extends FunctionalTestCase
{
    /** @test */
    public function empty_search_code_is_valid(): void
    {
        $client = self::newClient(['config' => 'search_processor.yml']);

        $client->request('GET', '/search');

        self::assertEquals('VALID: EMPTY', $client->getResponse()->getContent());
    }

    /** @test */
    public function post_condition(): void
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

    /** @test */
    public function invalid_condition_has_errors(): void
    {
        $client = self::newClient(['config' => 'search_processor.yml']);

        $client->request('GET', '/search?search=first-name%3A%20user%3B');

        self::assertEquals('INVALID: <ul><li>The field "first-name" is not registered in the FieldSet or available as alias.</li></ul>', $client->getResponse()->getContent());
    }
}
