<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;

abstract class FormatterTestCase extends SearchIntegrationTestCase
{
    /**
     * @var FieldSet
     */
    protected $fieldSet;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    protected function setUp()
    {
        parent::setUp();

        $this->fieldSet = $this->getFieldSet();
    }
}
