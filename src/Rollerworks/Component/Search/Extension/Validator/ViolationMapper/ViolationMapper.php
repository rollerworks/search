<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Validator\ViolationMapper;

use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ViolationMapper implements ViolationMapperInterface
{
    /**
     * Maps a constraint violation to a ValuesGroup in the ValuesGroup tree under
     * the given ValuesGroup.
     *
     * @param ConstraintViolationInterface $violation  The violations to map.
     * @param ValuesGroup                  $valuesGroup The root group of the tree to map it to.
     */
    public function mapViolation(ConstraintViolationInterface $violation, ValuesGroup $valuesGroup)
    {
        /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
        $propertyPath = new PropertyPath($violation->getPropertyPath());
        $it = $propertyPath->getIterator();

        // Because violations can only be set on a ValuesBag object
        // Its save to just ignore the type and value-index on the property-path
        $totalIdx = count($it);

        for ($currentIdx = 0; $currentIdx < $totalIdx; $currentIdx++) {
            $subPath = $it[$currentIdx];

            if ('fields' === $subPath) {
                $currentIdx++; // skip to field-name

                $valuesGroup->setHasErrors(true);
                $currentBag = $valuesGroup->getField($it[$currentIdx]);
                $currentIdx++;

                // The ValuesError contains the actual path to the value and is added on to ValuesBag object
                // Because some errors are added on the value-container rather then the value the path-length may vary
                // A value-container acts like "singleValue[1]", and value-pointer as "ranges[1].lower"

                if (($currentIdx + 3) === $totalIdx) {
                    $subPath = sprintf('%s[%d].%s', $it[$currentIdx++], $it[$currentIdx++], $it[$currentIdx]);
                } else {
                    $subPath = sprintf('%s[%d]', $it[$currentIdx++], $it[$currentIdx]);
                }

                $currentBag->addError(new ValuesError(
                    $subPath,
                    $violation->getMessage(),
                    $violation->getMessageTemplate(),
                    $violation->getMessageParameters(),
                    $violation->getMessagePluralization())
                );

                // Stop the loop as we have found the final path
                break;
            }

            // Set that the values-group has errors, this helps speeding-up the showing of errors
            $valuesGroup->setHasErrors(true);

            // We know we're not at a fields-node so we can safely skip this sub-path
            // and use the next sub-path value as group-index
            $currentIdx++;

            $valuesGroup = $valuesGroup->getGroup($it[$currentIdx]);
        }
    }
}
