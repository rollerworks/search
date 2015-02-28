<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;

abstract class SearchConditionOptimizerTestCase extends SearchIntegrationTestCase
{
    /**
     * @var FieldSet
     */
    protected $fieldSet;

    /**
     * @var SearchConditionOptimizerInterface
     */
    protected $optimizer;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldSet = $this->getFieldSet();
    }
}
