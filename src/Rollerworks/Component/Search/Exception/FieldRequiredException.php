<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exception;

class FieldRequiredException extends \RuntimeException
{
    private $fieldName;
    private $groupIdx;
    private $nestingLevel;

    /**
     * @param string  $fieldName
     * @param integer $groupIdx
     * @param integer $nestingLevel
     */
    public function __construct($fieldName, $groupIdx, $nestingLevel)
    {
        $this->fieldName = $fieldName;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(sprintf('Field "%s" is required but is missing in group %d at nesting level %d', $fieldName, $groupIdx, $nestingLevel));
    }
}
