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
use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ConditionStructureByViewBuilder extends ConditionStructureBuilder
{
    protected function inputToNorm($value, string $path)
    {
        if ($this->inputTransformer === null) {
            $this->inputTransformer = $this->fieldConfig->getViewTransformer() ?? false;
        }

        if (! $this->inputTransformer) {
            if ($value !== null && ! \is_scalar($value)) {
                $e = new \RuntimeException(
                    \sprintf(
                        'View value of type %s is not a scalar value or null and not cannot be ' .
                        'converted to a string. You must set a ViewTransformer for field "%s" with type "%s".',
                        \gettype($value),
                        $this->fieldConfig->getName(),
                        \get_class($this->fieldConfig->getType()->getInnerType())
                    )
                );

                $error = new ConditionErrorMessage(
                    $path,
                    $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                    $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                    $this->fieldConfig->getOption('invalid_message_parameters', []),
                    null,
                    $e
                );

                $this->addError($error);

                return null;
            }

            return $value === '' ? null : $value;
        }

        try {
            return $this->inputTransformer->reverseTransform($value);
        } catch (TransformationFailedException $e) {
            $this->addError($this->transformationExceptionToError($e, $path));

            return null;
        }
    }
}
