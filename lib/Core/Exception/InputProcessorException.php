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

use Rollerworks\Component\Search\ConditionErrorMessage;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class InputProcessorException extends \InvalidArgumentException implements SearchException
{
    public $path = '';
    public $messageTemplate;
    public $messageParameters;
    public $translatedParameters = [];
    public $plural;

    public function __construct(string $path, string $messageTemplate, array $messageParameters = [], int $plural = null, \Exception $previous = null)
    {
        $this->path = $path;
        $this->messageTemplate = $messageTemplate;
        $this->messageParameters = $messageParameters;
        $this->plural = $plural;

        parent::__construct(strtr($messageTemplate, $this->formatParameters($messageParameters)), 0, $previous);
    }

    public function toErrorMessageObj(): ConditionErrorMessage
    {
        $message = new ConditionErrorMessage(
            $this->path,
            $this->getMessage(),
            $this->messageTemplate,
            $this->messageParameters,
            $this->plural,
            $this
        );
        $message->setTranslatedParameters($this->translatedParameters);

        return $message;
    }

    /**
     * Set which parameters (by key) need to be translated.
     *
     * This helps with the translating of normalized types to
     * a localized format.
     *
     * @param array $translatedParameters An array of parameter names that need
     *                                    to be translated prior to their usage
     *
     * @return InputProcessorException
     */
    protected function setTranslatedParameters(array $translatedParameters): self
    {
        $this->translatedParameters = $translatedParameters;

        return $this;
    }

    private function formatParameters(array $messageParameters): array
    {
        $newParams = [];

        foreach ($messageParameters as $name => $value) {
            if (\is_array($value)) {
                $value = implode(', ', array_map([$this, 'formatValue'], $value));
            } else {
                $value = $this->formatValue($value);
            }

            $newParams[$name] = $value;
        }

        return $newParams;
    }

    private function formatValue($value): string
    {
        $value = (string) $value;

        if (ctype_punct($value)) {
            $value = '"'.$value.'"';
        }

        return $value;
    }
}
