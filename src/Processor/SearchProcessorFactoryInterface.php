<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Processor;

use Rollerworks\Component\Search\Input\ProcessorConfig;

interface SearchProcessorFactoryInterface
{
    /**
     * Creates a new SearchProcessorInterface object instance.
     *
     * @param ProcessorConfig $config    Input Processor configuration object
     * @param string          $uriPrefix URL prefix to allow multiple processors per page
     *
     * @return SearchProcessorInterface
     */
    public function createProcessor(ProcessorConfig $config, $uriPrefix = '');
}
