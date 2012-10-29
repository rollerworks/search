<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Input;

use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Rollerworks\Bundle\RecordFilterBundle\Exception\ValidationException;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;

/**
 * JsonInput - accepts filtering preference in the JSON format.
 *
 * The provided input must be structured.
 * The root is an array where each entry is a group with { "fieldname": { structure } }
 *
 * There structure can contain the following.
 *
 *  "single-values":    [ "value1", "value2" ]
 *  "excluded-values":  [ "my value1", "my value2" ]
 *  "ranges":           [ { "lower": 10, "upper": 20 } ]
 *  "excluded-ranges":  [ { "lower": 25, "upper": 30 } ]
 *  "comparisons":      [ { "value": 50,"operator": ">" } ]
 *
 * "Value" must must be either an integer or string.
 * Note: Big integers will be converted to strings.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
class JsonInput extends ArrayInput
{
    /**
     * @var string
     */
    protected $input;

    /**
     * Sets the filtering preference.
     *
     * @param string $input
     *
     * @return self
     */
    public function setInput($input)
    {
        $this->messages = new MessageBag($this->translator);
        $this->parsed = false;
        $this->input = trim($input);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        if ($this->parsed) {
            return $this->groups;
        }

        if (!$this->input) {
            throw new \InvalidArgumentException('No filtering preference provided.');
        }

        try {
            $this->validateSyntax($this->input);
            $groups = json_decode($this->input, true);

            foreach ($groups as $i => $group) {
                $this->processGroup($group, $i + 1);
            }
        } catch (ValidationException $e) {
            $this->messages->addError($e->getMessage(), $e->getParams());

            return false;
        }

        return $this->groups;
    }

    /**
     * @param string $json
     *
     * @return boolean true on success
     *
     * @throws ParsingException
     * @throws \UnexpectedValueException
     */
    protected static function validateSyntax($json)
    {
        $parser = new JsonParser();
        $result = $parser->lint($json);
        if (null === $result) {
            if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
                throw new \UnexpectedValueException('Input is not UTF-8, could not parse as JSON');
            }

            return true;
        }

        throw new ParsingException('Input does not contain valid JSON'."\n".$result->getMessage(), $result->getDetails());
    }
}
