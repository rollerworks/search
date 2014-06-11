<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Validator;

use Rollerworks\Component\Search\Extension\Validator\Constraints\ValuesGroup as ValuesGroupConstraint;
use Rollerworks\Component\Search\Extension\Validator\ViolationMapper\ViolationMapper;
use Rollerworks\Component\Search\Extension\Validator\ViolationMapper\ViolationMapperInterface;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Symfony\Component\Validator\ValidatorInterface;

/**
 * Validates the values using the configured constraints
 * of the corresponding field.
 *
 * Any violation is then mapped on the ValuesBag and ValuesGroup.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValidationFormatter implements FormatterInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var ViolationMapperInterface
     */
    private $violationMapper;

    /**
     * @param ValidatorInterface       $validator
     * @param ViolationMapperInterface $violationMapper
     */
    public function __construct(ValidatorInterface $validator, ViolationMapperInterface $violationMapper = null)
    {
        $this->validator = $validator;
        $this->violationMapper = $violationMapper ?: new ViolationMapper();
    }

    /**
     * {@inheritDoc}
     */
    public function format(SearchConditionInterface $condition)
    {
        if (true === $condition->getValuesGroup()->hasErrors()) {
            return;
        }

        $group = $condition->getValuesGroup();
        $violations = $this->validator->validateValue($condition, new ValuesGroupConstraint());

        if (count($violations) > 0) {
            foreach ($violations as $violation) {
                $this->violationMapper->mapViolation($violation, $group);
            }
        }
    }
}
