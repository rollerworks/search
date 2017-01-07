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
final class FieldValuesByViewFactory extends FieldValuesFactory
{
    protected function inputToNorm($value, string $path)
    {
        if (!$this->viewTransformer) {
            if (null !== $value && !is_scalar($value)) {
                $e = new \RuntimeException(
                    sprintf(
                        'View value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a ViewTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $this->config->getName(),
                        get_class($this->config->getType()->getInnerType())
                    )
                );

                $error = new ConditionErrorMessage(
                    $path,
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message_parameters', []),
                    null,
                    $e
                );

                $this->addError($error);

                return null;
            }

            return '' === $value ? null : $value;
        }

        try {
            return $this->viewTransformer->reverseTransform($value);
        } catch (TransformationFailedException $e) {
            $error = new ConditionErrorMessage(
                $path,
                $this->config->getOption('invalid_message', $e->getMessage()),
                $this->config->getOption('invalid_message', $e->getMessage()),
                $this->config->getOption('invalid_message_parameters', []),
                null,
                $e
            );

            $this->addError($error);

            return null;
        }
    }
}
