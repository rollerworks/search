<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

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
final class JsonInput implements InputProcessor
{
    private $processor;

    /**
     * Constructor.
     *
     * @param Validator|null $validator
     */
    public function __construct(Validator $validator = null)
    {
        $this->processor = new ArrayInput($validator);
    }

    /**
     * {@inheritdoc}
     */
    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        if (!is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        if (empty($input)) {
            return new SearchCondition($config->getFieldSet(), new ValuesGroup());
        }

        $array = json_decode($input, true, 512, \JSON_BIGINT_AS_STRING);

        if (json_last_error() !== \JSON_ERROR_NONE) {
            throw new InvalidSearchConditionException([
                ConditionErrorMessage::rawMessage(
                    $input,
                    'Input does not contain valid JSON: '."\n".json_last_error_msg(),
                    $input
                ),
            ]);
        }

        return $this->processor->process($config, $array);
    }
}
