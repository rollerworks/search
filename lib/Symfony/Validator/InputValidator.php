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

namespace Rollerworks\Component\Search\Extension\Symfony\Validator;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Input\Validator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates input values using the Symfony Validator.
 *
 * The search field must have a `constraints` option set
 * or else it's ignored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class InputValidator implements Validator
{
    private $validator;
    /** @var FieldConfig */
    private $field;
    /** @var ErrorList */
    private $errorList;
    /** @var array */
    private $constraints = [];

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function initializeContext(FieldConfig $field, ErrorList $errorList): void
    {
        $this->field = $field;
        $this->errorList = $errorList;

        $this->constraints = $field->getOption('constraints', null);
    }

    public function validate($value, string $type, $originalValue, string $path): bool
    {
        if (null === $this->constraints) {
            return true;
        }

        $violations = $this->validator->validate($value, $this->constraints);

        foreach ($violations as $violation) {
            $this->errorList[] = new ConditionErrorMessage(
                $path,
                $violation->getMessage(),
                $violation->getMessageTemplate(),
                $violation->getParameters(),
                $violation->getPlural(),
                $violation
            );
        }

        return !\count($violations);
    }
}
