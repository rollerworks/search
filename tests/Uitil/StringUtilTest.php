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

namespace Rollerworks\Component\Search\Tests\Uitil;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Util\StringUtil;

/**
 * @internal
 */
final class StringUtilTest extends TestCase
{
    /**
     * @dataProvider fqcnToBlockPrefixProvider
     */
    public function testFqcnToBlockPrefix($fqcn, $expectedBlockPrefix)
    {
        $blockPrefix = StringUtil::fqcnToBlockPrefix($fqcn);

        $this->assertSame($expectedBlockPrefix, $blockPrefix);
    }

    public function fqcnToBlockPrefixProvider()
    {
        return [
            ['TYPE', 'type'],
            ['\Type', 'type'],
            ['\UserType', 'user'],
            ['UserType', 'user'],
            ['Vendor\Name\Space\Type', 'type'],
            ['Vendor\Name\Space\UserForm', 'user_form'],
            ['Vendor\Name\Space\UserType', 'user'],
            ['Vendor\Name\Space\usertype', 'user'],
            ['Symfony\Component\Form\Form', 'form'],
            ['Vendor\Name\Space\BarTypeBazType', 'bar_type_baz'],
            ['FooBarBazType', 'foo_bar_baz'],
        ];
    }
}
