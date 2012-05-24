<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Annotation;

/**
 * Annotation class for Filtering fields.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @Annotation
 */
class Field
{
    private $name;

    private $required;

    private $type;

    private $acceptRanges;

    private $acceptCompares;

    private $params = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters.
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
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setRequired($required)
    {
        $this->required = $required;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setAcceptRanges($accept)
    {
        $this->acceptRanges = $accept;
    }

    public function acceptsRanges()
    {
        return $this->acceptRanges;
    }

    public function setAcceptCompares($accept)
    {
        $this->acceptCompares = $accept;
    }

    public function acceptsCompares()
    {
        return $this->acceptCompares;
    }

    public function hasParams()
    {
        return count($this->params) > 0;
    }

    public function getParams()
    {
        return $this->params;
    }
}
