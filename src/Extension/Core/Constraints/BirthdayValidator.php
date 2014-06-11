<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class BirthdayValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value || ($constraint->allowAge && ctype_digit($value))) {
            return;
        }

        $message = $constraint->allowAge ? $constraint->ageMessage : $constraint->dateMessage;

        if ($value instanceof \DateTime) {
            $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $currentDate->setTime(0, 0, 0);

            // Force the UTC timezone with 00:00:00 for correct comparison
            $value = clone $value;
            $value->setTimezone(new \DateTimeZone('UTC'));
            $value->setTime(0, 0, 0);

            if ($value > $currentDate) {
                $this->context->addViolation($message, array('{{ value }}' => (string) $value));
            }

            return;
        }

        $this->context->addViolation($message, array('{{ value }}' => (string) $value));
    }
}
