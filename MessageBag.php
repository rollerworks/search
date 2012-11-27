<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * MessageBag.
 */
class MessageBag
{
    /**
     * Information message-type.
     */
    const MSG_INFO = 'info';

    /**
     * Error message-type.
     */
    const MSG_ERROR = 'error';

    /**
     * @var array
     */
    protected $messages = array('info' => array(), 'error' => array());

    /**
     * @var array
     */
    protected $params = array();

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sets the default params for the translator, these will be merged with the message params.
     *
     * @param array $params
     *
     * @return self
     */
    public function setTranslatorParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Returns params for the translator.
     *
     * @return array
     */
    public function getTranslatorParams()
    {
        return $this->params;
    }

    /**
     * Gets all the messages.
     *
     * @return array
     */
    public function all()
    {
        return $this->messages;
    }

    /**
     * Gets all the messages by type.
     *
     * @param string $type
     *
     * @return array
     *
     * @throws \UnexpectedValueException On invalid type
     */
    public function get($type)
    {
        if ('error' !== $type && 'info' !== $type) {
            throw new \UnexpectedValueException('type must be error or info.');
        }

        return $this->messages[$type];
    }

    /**
     * Returns whether the bag contains messages of this type.
     *
     * @param string $type
     *
     * @return boolean
     *
     * @throws \UnexpectedValueException On invalid type
     */
    public function has($type)
    {
        if ('error' !== $type && 'info' !== $type) {
            throw new \UnexpectedValueException('type must be error or info.');
        }

        return count($this->messages[$type]) > 0;
    }

    /**
     * @param string       $message The error message.
     * @param array        $params  The parameters parsed into the error message.
     * @param null|integer $plural  The number to use to pluralize of the message.
     * @param string       $domain  Alternative translation domain
     */
    public function addError($message, array $params = array(), $plural = null, $domain = 'validators')
    {
        if ($plural) {
            $this->messages['error'][] = $this->translator->transChoice($message, $plural, $params + $this->params, $domain);
        } else {
            $this->messages['error'][] = $this->translator->trans($message, $params + $this->params, $domain);
        }
    }

    /**
     * @param string       $message The info message.
     * @param array        $params  The parameters parsed into the info message.
     * @param null|integer $plural  The number to use to pluralize of the message.
     * @param string       $domain  Alternative translation domain
     */
    public function addInfo($message, array $params = array(), $plural = null, $domain = 'messages')
    {
        if ($plural) {
            $this->messages['info'][] = $this->translator->transChoice($message, $plural, $params + $this->params, $domain);
        } else {
            $this->messages['info'][] = $this->translator->trans($message, $params + $this->params, $domain);
        }
    }

    /**
     * Clones the current Bag and resets the messages.
     */
    public function __clone()
    {
        $this->messages = array('info' => array(), 'error' => array());
    }
}
