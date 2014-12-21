<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\InputProcessorException;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * JsonInput processes input provided as an JSON object.
 *
 * The required input structure is the same as the {@link ArrayInput].
 *
 * The main advantage of using this Class rather then decoding the JSON object yourself
 * is that this class lints if the provided JSON object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JsonInput extends ArrayInput
{
    /**
     * {@inheritdoc}
     */
    public function process($input)
    {
        try {
            $this->validateSyntax($input);
        } catch (ParsingException $e) {
            throw new InputProcessorException('Provided input is invalid.', 0, $e);
        }

        $array = json_decode($input, true, $this->maxNestingLevel+10);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InputProcessorException(
                'Provided input is invalid, JSON contains an error or the maximum stack depth has been exceeded.',
                json_last_error()
            );
        }

        return parent::process($array);
    }

    /**
     * @param string $json
     *
     * @return bool true on success
     *
     * @throws ParsingException
     */
    private static function validateSyntax($json)
    {
        $parser = new JsonParser();
        $result = $parser->lint($json);
        if (null === $result) {
            if (defined('JSON_ERROR_UTF8') && JSON_ERROR_UTF8 === json_last_error()) {
                throw new ParsingException('Input is not UTF-8, could not parse as JSON');
            }

            return true;
        }

        throw new ParsingException(
            'Input does not contain valid JSON: '."\n".$result->getMessage(),
            $result->getDetails()
        );
    }
}
