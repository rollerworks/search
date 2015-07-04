<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class UnknownFieldException extends InputProcessorException
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * Constructor.
     *
     * @param string $fieldName
     */
    public function __construct($fieldName)
    {
        $this->fieldName = $fieldName;

        parent::__construct(
            sprintf('Field "%s" is not registered in the FieldSet or available as alias.', $fieldName)
        );
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
