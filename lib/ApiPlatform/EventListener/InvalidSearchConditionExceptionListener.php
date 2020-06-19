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

namespace Rollerworks\Component\Search\ApiPlatform\EventListener;

use ApiPlatform\Core\Util\ErrorFormatGuesser;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Serializer\SerializerInterface;

final class InvalidSearchConditionExceptionListener
{
    private $serializer;
    private $errorFormats;

    public function __construct(SerializerInterface $serializer, array $errorFormats)
    {
        $this->serializer = $serializer;
        $this->errorFormats = $errorFormats;
    }

    /**
     * Returns a list of errors normalized in the Hydra format.
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof InvalidSearchConditionException) {
            return;
        }

        $format = ErrorFormatGuesser::guessErrorFormat($event->getRequest(), $this->errorFormats);

        $event->setResponse(new Response(
                $this->serializer->serialize($exception, $format['key']),
                Response::HTTP_BAD_REQUEST,
                [
                    'Content-Type' => sprintf('%s; charset=utf-8', $format['value'][0]),
                    'X-Content-Type-Options' => 'nosniff',
                    'X-Frame-Options' => 'deny',
                ]
        ));
    }
}
