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

    private $widgets = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters.
     * @throws \BadMethodCallException
     * @throws \UnexpectedValueException
     */
    public function __construct(array $data)
    {
        $this->name           = null;
        $this->type           = null;

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
            // Widgets are configured per widget-type
            elseif (preg_match('/^widget_([^_]+)_(.+)/i', $key, $widgetParams)) {
                $widgetType = $widgetParams[1];
                $widgetKey  = $widgetParams[2];

                $this->widgets[$widgetType][$widgetKey] = $value;
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

    function setName($name)
    {
        $this->name = $name;
    }

    function getName()
    {
        return $this->name;
    }

    function setRequired($required)
    {
        $this->required = $required;
    }

    function isRequired()
    {
        return $this->required;
    }

    function setType($type)
    {
        $this->type = $type;
    }

    function getType()
    {
        return $this->type;
    }

    function setAcceptRanges($accept)
    {
        $this->acceptRanges = $accept;
    }

    function acceptsRanges()
    {
        return $this->acceptRanges;
    }

    function setAcceptCompares($accept)
    {
        $this->acceptCompares = $accept;
    }

    function acceptsCompares()
    {
        return $this->acceptCompares;
    }

    function hasParams()
    {
        return count($this->params);
    }

    function getParams()
    {
        return $this->params;
    }

    function getWidget($type = null)
    {
        if (null === $type) {
            return $this->widgets;
        }

        if (isset($this->widgets[$type])) {
            return $this->widgets[$type];
        }
        else {
            return array();
        }
    }
}
