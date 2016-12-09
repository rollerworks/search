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
class InputProcessorException extends \InvalidArgumentException implements ExceptionInterface
{
    public $path = '';
    public $messageTemplate;
    public $messageParameters;
    public $plural;

    protected $translatedParameters = [];
    private $translator;

    public function __construct(string $path, string $messageTemplate, array $messageParameters = [], int $plural = null, \Exception $previous = null)
    {
        $this->path = $path;
        $this->messageTemplate = $messageTemplate;
        $this->messageParameters = $messageParameters;
        $this->plural = $plural;

        parent::__construct($this->render(), 0, $previous);
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
     * Returns the translated (rendered) error message.
     *
     * @param callable $translator Callback to translate the message
     *                             prototype: string $id, [array $parameters], [int $plural]
     *
     * @return string
     */
    public function render(callable $translator = null): string
    {
        $this->translator = $translator ?? [$this, 'translateId'];

        return call_user_func($this->translator,
            $this->messageTemplate,
            $this->parseMessageParameters($this->messageParameters),
            $this->plural
        );
    }

    /**
     * @param array $translatedParameters An array of parameter names that need
     *                                    to be translated prior to there usage
     */
    protected function setTranslatedParameters(array $translatedParameters)
    {
        $this->translatedParameters = $translatedParameters;
    }

    protected function translateId(string $id, array $parameters = []): string
    {
        return strtr((string) $id, $parameters);
    }

    private function parseMessageParameters(array $messageParameters): array
    {
        $newParams = [];

        foreach ($messageParameters as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map([$this, 'formatValue'], array_keys($value), $value));
            } else {
                $value = $this->formatValue($name, $value);
            }

            $newParams[$name] = $value;
        }

        return $newParams;
    }

    private function formatValue($key, $value): string
    {
        $value = (string) $value;

        if (in_array($key, $this->translatedParameters, true)) {
            $value = call_user_func($this->translator, $value, []);
        }

        if (ctype_punct($value)) {
            $value = '"'.$value.'"';
        }

        return (string) $value;
    }
}
