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

namespace Rollerworks\Component\Search\Field;

use Rollerworks\Component\Search\FieldSetView;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchFieldView
{
    /**
     * The variables assigned to this view.
     *
     * @var array
     */
    public $vars = [
        'attr' => [],
    ];

    /**
     * @var FieldSetView
     */
    public $fieldSet;

    /**
     * Constructor.
     *
     * @param FieldSetView $fieldSet
     */
    public function __construct(FieldSetView $fieldSet)
    {
        $this->fieldSet = $fieldSet;
    }
}
