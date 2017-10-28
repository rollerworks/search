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

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionOptimizer;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class SearchConditionOptimizerTestCase extends SearchIntegrationTestCase
{
    /**
     * @var FieldSet|null
     */
    protected $fieldSet;

    /**
     * @var SearchConditionOptimizer|null
     */
    protected $optimizer;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldSet = $this->getFieldSet();
    }
}
