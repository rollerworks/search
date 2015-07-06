<?php

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
    public function testEmptyFilterCodeIsValid()
    {
        $client = self::newClient();

        $client->request('GET', '/search');

        $this->assertEquals('VALID: EMPTY', $client->getResponse()->getContent());
    }

    public function testInvalidConditionHasErrors()
    {
        $client = self::newClient();

        $client->request('POST', '/search', ['rollerworks_search' => ['filter' => 'name: user;']]);

        $this->assertEquals('INVALID: <ul><li>Field &quot;name&quot; is not registered in the FieldSet or available as alias.</li></ul>', $client->getResponse()->getContent());
    }
}
