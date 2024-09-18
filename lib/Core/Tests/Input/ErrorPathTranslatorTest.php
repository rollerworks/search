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

namespace Rollerworks\Component\Search\Tests\Input;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Input\ErrorPathTranslator;
use Symfony\Component\Translation\Translator;

/**
 * @internal
 */
final class ErrorPathTranslatorTest extends TestCase
{
    /**
     * @test
     *
     * @dataProvider provide_it_translates_to_human_friendly_format
     */
    public function it_translates_to_human_friendly_format(string $input, string $expected): void
    {
        $translator = new Translator('en');
        $humanizer = new ErrorPathTranslator($translator);

        self::assertEquals($expected, $humanizer->translateFromQueryString($input), 'With input:' . $input);
    }

    public static function provide_it_translates_to_human_friendly_format(): iterable
    {
        // Empty
        yield ['', ''];

        // No value position
        yield ['[@id]', 'At root level, field "@id":'];

        // Fields at root level
        yield ['[email][0]', 'At root level, field "email" value position 1:'];
        yield ['[email][1]', 'At root level, field "email" value position 2:'];

        // Field value with type
        yield ['[id][2][lower]', 'At root level, field "id" value position 3 lower:'];
        yield ['[id][2][upper]', 'At root level, field "id" value position 3 upper:'];

        // At group at root level (no field)
        yield ['[2]', 'At root level, group 3:'];

        // In (nested) group
        yield ['[2][email][3]', 'In group 3, field "email" value position 4:'];
        yield ['[0][0][email][0]', 'In nested group 1.1, field "email" value position 1:'];
        yield ['[1][2][email][3]', 'In nested group 2.3, field "email" value position 4:'];
        yield ['[1][2][email][3][upper]', 'In nested group 2.3, field "email" value position 4 upper:'];
        yield ['[1][2][email]', 'In nested group 2.3, field "email":'];
    }
}
