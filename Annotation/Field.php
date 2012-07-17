<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Annotation;

/**
 * Annotation class for Filtering fields.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @Annotation
 */
class Field
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var boolean
     */
    private $required;

    /**
     * @var Type
     */
    private $type;

    /**
     * @var boolean
     */
    private $acceptRanges;

    /**
     * @var boolean
     */
    private $acceptCompares;

    /**
     * @var array
     */
    private $params = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters
     *
     * @throws \BadMethodCallException
     * @throws \UnexpectedValueException
     */
    public function __construct(array $data)
    {
        $this->name = null;
        $this->type = null;

        $this->required       = false;
        $this->acceptRanges   = false;
        $this->acceptCompares = false;

        if (isset($data['value'])) {
            $data['name'] = $data['value'];
            unset($data['value']);
        }

        if (isset($data['req'])) {
            $data['required'] = $data['req'];
            unset($data['req']);
        }

        foreach ($data as $key => $value) {
            if ('_' === mb_substr($key, 0, 1)) {
                $this->params[ mb_substr($key, 1) ] = $value;
                continue;
            }

            $method = 'set' . ucfirst($key);

            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }

            $this->$method($value);
        }

        if (empty($this->name)) {
            throw new \UnexpectedValueException(sprintf("Property '%s' on annotation '%s' is required.", 'name', get_class($this)));
        }

        if (null === $this->type) {
            $this->type = new Type(array('value' => null));
        }
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param boolean $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }

    /**
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param Type $type
     */
    public function setType($type)
    {
        if (!$type instanceof Type) {
            $type = new Type(array('value' => $type));
        }

        $this->type = $type;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param boolean $accept
     */
    public function setAcceptRanges($accept)
    {
        $this->acceptRanges = $accept;
    }

    /**
     * @return boolean
     */
    public function acceptsRanges()
    {
        return $this->acceptRanges;
    }

    /**
     * @param boolean $accept
     */
    public function setAcceptCompares($accept)
    {
        $this->acceptCompares = $accept;
    }

    /**
     * @return boolean
     */
    public function acceptsCompares()
    {
        return $this->acceptCompares;
    }

    /**
     * @return boolean
     */
    public function hasParams()
    {
        return count($this->params) > 0;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
