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

namespace Rollerworks\Component\Search;

/**
 * A ConditionErrorMessage holds an error message,
 * produced during processing.
 *
 * This can be a failed value transformation,
 * too deep nesting, or values overflow.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ConditionErrorMessage
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $message;

    /**
     * The template for the error message.
     *
     * @var string|null
     */
    public $messageTemplate;

    /**
     * The parameters that should be substituted in the message template.
     *
     * @var array
     */
    public $messageParameters;

    /**
     * The value for error message pluralization.
     *
     * @var int|null
     */
    public $messagePluralization;

    public $cause;

    /**
     * @var string[]
     */
    public $translatedParameters;

    /**
     * Any array key in $messageParameters will be used as a placeholder in
     * $messageTemplate.
     *
     * @param string      $path                 Path of the error, this is dependent
     *                                          on the values structure
     * @param string      $message              The translated error message
     * @param string|null $messageTemplate      The template for the error message
     * @param array       $messageParameters    The parameters that should be
     *                                          substituted in the message template
     * @param int|null    $messagePluralization The value for error message pluralization
     * @param mixed       $cause                The cause of the error
     */
    public function __construct(string $path, string $message, ?string $messageTemplate = null, array $messageParameters = [], ?int $messagePluralization = null, $cause = null)
    {
        $this->path = $path;
        $this->message = $message;
        $this->messageTemplate = $messageTemplate ?: $message;
        $this->messageParameters = $messageParameters;
        $this->messagePluralization = $messagePluralization;
        $this->cause = $cause;
    }

    public static function withMessageTemplate(string $path, string $messageTemplate, array $messageParameters = [], int $messagePluralization = null, $cause = null): self
    {
        return new static(
            $path,
            \strtr($messageTemplate, $messageParameters),
            $messageTemplate,
            $messageParameters,
            $messagePluralization,
            $cause
        );
    }

    public static function rawMessage(string $path, string $message, $cause = null): self
    {
        $obj = new static($path, $message, null, [], null, $cause);
        $obj->messageTemplate = null; // Mark as untranslated

        return $obj;
    }

    /**
     * @param array $translatedParameters An array of parameter names that need
     *                                    to be translated prior to there usage
     */
    public function setTranslatedParameters(array $translatedParameters): self
    {
        $this->translatedParameters = $translatedParameters;

        return $this;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
