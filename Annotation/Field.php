<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
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

    private $_aParams = array();

    private $_aWidgets = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters.
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
                $this->_aParams[ mb_substr($key, 1) ] = $value;
                continue;
            }
            // Widgets are configured per widget-type
            elseif (preg_match('/^widget_([^_]+)_(.+)/i', $key, $aWidgetParams)) {
                $sWidgetType = $aWidgetParams[1];
                $sWidgetKey  = $aWidgetParams[2];

                $this->_aWidgets[$sWidgetType][$sWidgetKey] = $value;
                continue;
            }

            $method = 'set' . ucfirst($key);

            if (! method_exists($this, $method)) {
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
        return count($this->_aParams);
    }

    function getParams()
    {
        return $this->_aParams;
    }

    function getWidget($type)
    {
        if (isset($this->_aWidgets[$type])) {
            return $this->_aWidgets[$type];
        }
        else {
            return array();
        }
    }
}