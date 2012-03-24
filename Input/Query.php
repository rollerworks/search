<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
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

namespace Rollerworks\RecordFilterBundle\Input;

/**
 * Accept input in an filter-query format.
 *
 * Every filter is an: name=values;
 *
 * The field name must follow this regex convention: [a-z][a-z_0-9]*.
 * Unicode characters are accepted.
 *
 * If the value contains an ';' or '()', the whole value must be quoted (with double quotes).
 * If the value contains an special character, like the range symbol 'that' value-part must be quoted.
 * Like: "value-1"-value2
 *
 * Single values containing no special characters, can be quoted. But this is not required.
 *
 * If you want to use OR-cases place the name=value; between round-bars '()'
 * and separate them by one comma ','.
 *
 * Important: the field=value pairs must 'always end' with an ';', especially when in an or-group.
 * The parser will not accept an input like: (field=value),(field2=value)
 *
 * Comma at the end is always ignored.
 *
 * === Prefix ===
 *
 * If you want to provide an global search field in your application,
 *  you can prefix the filter-query with an section like: section; field1=value
 *
 * Multiple sections are accepted, and are separated by comma.
 * Sections apply globally, *not* per OR-group.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Query extends AbstractInput
{
    /**
     * State of the parser
     *
     * @var boolean
     */
    protected $isParsed = false;

    /**
     * Filter-query (as-is)
     *
     * @var string
     */
    protected $query = null;

    /**
     * Section where the filter-query can be used.
     *
     * @var string
     */
    protected $sections = array();

    /**
     * Constructor
     *
     * @param string $query
     */
    public function __construct($query = null)
    {
        if (null !== $query) {
            $this->setQueryString($query);
        }
    }


    /**
     * Set the filter-query input
     *
     * @param string $query
     * @return Query
     */
    public function setQueryString($query)
    {
        $this->isParsed = false;
        $this->query    = trim($query);

        return $this;
    }

    /**
     * Get the filter-query
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->query;
    }

    /**
     * Get the section where the filtering can be used.
     * When none are set returns an empty array
     *
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * Get the input-values.
     *
     * The values are un-formatted or validated
     *
     * @return array
     */
    public function getValues()
    {
        if (false === $this->isParsed) {
            $this->parseQuery();
        }

        return $this->groups;
    }

    /**
     * Parse the query-filter that is set
     */
    protected function parseQuery()
    {
        // Look if there is an section prefix
        if (preg_match('/^((?:\p{L}[\p{L}\p{L}\d\p{M}\p{Pd}]*[;,])+)\h*/su', $this->query, $prefixMatch)) {
            $this->query = mb_substr($this->query, mb_strlen($prefixMatch[0]));

            $sections = explode(',', trim($prefixMatch[1], ',; '));
            $sections = array_unique($sections);

            $this->sections = $sections;
        }

        // Look for the usage of OR-group(s)
        // There is 'minor problem', the field-value pairs must end with an ;, or else the parsing is ignored.
        // Various solutions have been tried, but did not work...
        // But its still better, then to 'always' need to escape the grouping parentheses to use them as literals.
        if ('(' === mb_substr($this->query, 0, 1)) {
            if (preg_match_all('/\(((?:\s*(?:\p{L}[\p{L}\d]*)\s*=(?:(?:\s*(?:"(?:(?:[^"]+|"")+)"|[^;,]+)\s*,*)*);?\s*)*)\),?/us', $this->query, $groups)) {
                $groupsCount = count($groups[0]);

                for ($i = 0; $i < $groupsCount; $i++) {
                    $this->groups[$i] = $this->parseGroup($groups[1][$i]);
                }
            }

            $this->hasGroups = count($this->groups) > 1;
        }
        else {
            $this->groups[0] = $this->parseGroup($this->query);
        }

        $this->isParsed = true;
    }

    /**
     * Parse the field=value pairs from the input.
     * And return the pairs as array
     *
     * @param string $input
     * @return array
     */
    protected function parseGroup($input)
    {
        $filterPairs = array();

        if (preg_match_all('/(\p{L}[\p{L}\d]*)\s*=((?:\s*(?:"(?:(?:[^"]+|"")+)"|[^;,]+)\s*,*)*);?/us', $input, $filterPairMatches)) {
            $iFilters = count($filterPairMatches[0]);

            for ($iFilter = 0; $iFilter < $iFilters; $iFilter++) {
                $name  = mb_strtolower($filterPairMatches[1][$iFilter]);
                $value = $filterPairMatches[2][$iFilter];

                if (isset($filterPairs[$name])) {
                    $filterPairs[$name] .= ',' . $value;
                }
                else {
                    $filterPairs[$name] = $value;
                }
            }
        }

        return $filterPairs;
    }
}
