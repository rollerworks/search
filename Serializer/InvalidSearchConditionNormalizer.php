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

namespace Rollerworks\Component\Search\ApiPlatform\Serializer;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Converts {@see \Rollerworks\Component\Search\Exception\InvalidSearchConditionException}
 * to a Hydra error representation.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class InvalidSearchConditionNormalizer implements NormalizerInterface
{
    public const FORMAT = 'jsonproblem';

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Normalizes an object into a set of arrays/scalars.
     *
     * @param InvalidSearchConditionException $object  object to normalize
     * @param string                          $format  format the normalization result will be encoded as
     * @param array                           $context Context options for the normalizer
     *
     * @return array
     */
    public function normalize($object, $format = null, array $context = [])
    {
        $violations = [];
        $messages = [];

        foreach ($object->getErrors() as $message) {
            $violations[] = [
                'propertyPath' => $message->path,
                'message' => $message->message,
            ];

            $propertyPath = $message->path;
            $prefix = $propertyPath ? sprintf('%s: ', $propertyPath) : '';

            $messages[] = $prefix.$message->message;
        }

        return [
            '@context' => $this->urlGenerator->generate('api_jsonld_context', ['shortName' => 'ConstraintViolationList']),
            '@type' => 'ConstraintViolationList',
            'hydra:title' => $context['title'] ?? 'An error occurred',
            'hydra:description' => $messages ? implode("\n", $messages) : (string) $object,
            'violations' => $violations,
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return self::FORMAT === $format && $data instanceof InvalidSearchConditionException;
    }
}
