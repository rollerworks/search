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

namespace Rollerworks\Component\Search\ApiPlatform\Tests;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\ApiPlatform\Serializer\InvalidSearchConditionNormalizer;
use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @internal
 */
final class InvalidSearchConditionNormalizerTest extends TestCase
{
    /** @test */
    public function support_normalization(): void
    {
        $normalizer = new InvalidSearchConditionNormalizer();

        self::assertTrue($normalizer->supportsNormalization(new InvalidSearchConditionException([]), InvalidSearchConditionNormalizer::FORMAT));
        self::assertFalse($normalizer->supportsNormalization(new InvalidSearchConditionException([]), 'xml'));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass(), InvalidSearchConditionNormalizer::FORMAT));
    }

    /** @test */
    public function normalize(): void
    {
        $normalizer = new InvalidSearchConditionNormalizer();

        $list = new InvalidSearchConditionException([
            new ConditionErrorMessage('a', 'b'),
            new ConditionErrorMessage('', '2'),
        ]);

        $expected = [
            'type' => 'https://tools.ietf.org/html/rfc2616#section-10',
            'title' => 'An error occurred',
            'detail' => "a: b\n2",
            'violations' => [
                [
                    'propertyPath' => 'a',
                    'message' => 'b',
                ],
                [
                    'propertyPath' => '',
                    'message' => '2',
                ],
            ],
        ];
        self::assertEquals($expected, $normalizer->normalize($list));
    }
}
