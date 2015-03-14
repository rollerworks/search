<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

/**
 * JsonInput processes input provided as an JSON object.
 *
 * The required input structure is the same as the {@see \Rollerworks\Component\Search\Input\ArrayInput].
 *
 * The main advantage of using this Class rather then decoding the JSON object yourself
 * is that this class lints the provided JSON object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JsonInput extends ArrayInput
{
    /**
     * {@inheritdoc}
     */
    public function process(ProcessorConfig $config, $input)
    {
        if (!is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        if (empty($input)) {
            return;
        }

        try {
            $parser = new JsonParser();
            $array = $parser->parse($input, JsonParser::PARSE_TO_ASSOC);
        } catch (ParsingException $e) {
            throw new InputProcessorException('Input does not contain valid JSON: '."\n".$e->getMessage(), 0, $e);
        }

        return parent::process($config, $array);
    }
}
