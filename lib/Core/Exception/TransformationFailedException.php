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

namespace Rollerworks\Component\Search\Exception;

final class TransformationFailedException extends \RuntimeException implements SearchException
{
    private $invalidMessage;
    private $invalidMessageParameters;

    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null, ?string $invalidMessage = null, array $invalidMessageParameters = [])
    {
        parent::__construct($message, $code, $previous);

        $this->setInvalidMessage($invalidMessage, $invalidMessageParameters);
    }

    /**
     * Sets the message that will be shown to the user.
     *
     * @param string|null $invalidMessage           The message or message key
     * @param array       $invalidMessageParameters Data to be passed into the translator
     */
    public function setInvalidMessage(?string $invalidMessage = null, array $invalidMessageParameters = []): void
    {
        $this->invalidMessage = $invalidMessage;
        $this->invalidMessageParameters = $invalidMessageParameters;
    }

    public function getInvalidMessage(): ?string
    {
        return $this->invalidMessage;
    }

    public function getInvalidMessageParameters(): array
    {
        return $this->invalidMessageParameters;
    }
}
