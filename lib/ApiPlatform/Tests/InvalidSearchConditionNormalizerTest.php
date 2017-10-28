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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\ApiPlatform\Serializer\InvalidSearchConditionNormalizer;
use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class InvalidSearchConditionNormalizerTest extends TestCase
{
    public function testSupportNormalization()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);

        $normalizer = new InvalidSearchConditionNormalizer($urlGeneratorProphecy->reveal());

        self::assertTrue($normalizer->supportsNormalization(new InvalidSearchConditionException([]), InvalidSearchConditionNormalizer::FORMAT));
        self::assertFalse($normalizer->supportsNormalization(new InvalidSearchConditionException([]), 'xml'));
        self::assertFalse($normalizer->supportsNormalization(new \stdClass(), InvalidSearchConditionNormalizer::FORMAT));
    }

    public function testNormalize()
    {
        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList'])->willReturn('/context/foo')->shouldBeCalled();

        $normalizer = new InvalidSearchConditionNormalizer($urlGeneratorProphecy->reveal());

        $list = new InvalidSearchConditionException([
            new ConditionErrorMessage('a', 'b'),
            new ConditionErrorMessage('', '2'),
        ]);

        $expected = [
            '@context' => '/context/foo',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => 'a: b
2',
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
        $this->assertEquals($expected, $normalizer->normalize($list));
    }
}
