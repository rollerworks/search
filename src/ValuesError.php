<?php

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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesError
{
    /**
     * @var string
     */
    private $subPath;

    /**
     * @var string
     */
    private $message;

    /**
     * The template for the error message.
     *
     * @var string
     */
    private $messageTemplate;

    /**
     * The parameters that should be substituted in the message template.
     *
     * @var array
     */
    private $messageParameters;

    /**
     * The value for error message pluralization.
     *
     * @var int|null
     */
    private $messagePluralization;

    /**
     * @var mixed|null
     */
    private $cause;

    /**
     * Constructor.
     *
     * Any array key in $messageParameters will be used as a placeholder in
     * $messageTemplate.
     *
     * @param string      $subPath              Sub-path of the error, this is relative to
     *                                          the ValuesBag object.
     * @param string      $message              The translated error message
     * @param string|null $messageTemplate      The template for the error message
     * @param array       $messageParameters    The parameters that should be
     *                                          substituted in the message template.
     * @param int|null    $messagePluralization The value for error message pluralization
     * @param mixed       $cause                The cause of the error
     *
     * @see \Symfony\Component\Translation\Translator
     */
    public function __construct($subPath, $message, $messageTemplate = null, array $messageParameters = [], $messagePluralization = null, $cause = null)
    {
        $this->subPath = $subPath;
        $this->message = $message;
        $this->messageTemplate = $messageTemplate ?: $message;
        $this->messageParameters = $messageParameters;
        $this->messagePluralization = $messagePluralization;
        $this->cause = $cause;
    }

    /**
     * Returns the sub-path of the error.
     *
     * @return string
     */
    public function getSubPath()
    {
        return $this->subPath;
    }

    /**
     * Returns the error message.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Returns the error message template.
     *
     * @return string
     */
    public function getMessageTemplate()
    {
        return $this->messageTemplate;
    }

    /**
     * Returns the parameters to be inserted in the message template.
     *
     * @return array
     */
    public function getMessageParameters()
    {
        return $this->messageParameters;
    }

    /**
     * Returns the value for error message pluralization.
     *
     * @return int|null
     */
    public function getMessagePluralization()
    {
        return $this->messagePluralization;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getMessage();
    }

    /**
     * @return mixed|null
     */
    public function getCause()
    {
        return $this->cause;
    }

    /**
     * Returns the hash that identifies this error.
     *
     * Caution: This will only use the subPath + messageTemplate + messageParameters + messagePluralization as SHA1.
     * So an error with the same information but different cause will produce the same hash!
     *
     * @return string
     */
    public function getHash()
    {
        return sha1(
            $this->subPath.$this->messageTemplate.serialize($this->getMessageParameters()).$this->messagePluralization
        );
    }
}
