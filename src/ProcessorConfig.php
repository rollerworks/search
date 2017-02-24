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

namespace Rollerworks\Component\Search\Processor;

use Rollerworks\Component\Search\FieldSet;

class ProcessorConfig extends \Rollerworks\Component\Search\Input\ProcessorConfig
{
    private $requestPrefix;
    private $exportFormat;
    private $defaultFormat;
    private $cacheTTL;

    /**
     * @param FieldSet $fieldSet
     * @param string   $defaultFormat The default input/export format
     */
    public function __construct(FieldSet $fieldSet, string $defaultFormat = 'string_query')
    {
        parent::__construct($fieldSet);
        $this->defaultFormat = $defaultFormat;
    }

    /**
     * Set the prefix to use when there are multiple search
     * sections on the same page/end-point.
     *
     * @param string $requestPrefix
     *
     * @return ProcessorConfig
     */
    public function setRequestPrefix(string $requestPrefix = null): ProcessorConfig
    {
        $this->requestPrefix = $requestPrefix;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestPrefix(): ?string
    {
        return $this->requestPrefix;
    }

    /**
     * Set the default export format for the SearchCondition(s).
     *
     * @param string $exportFormat
     *
     * @return ProcessorConfig
     */
    public function setExportFormat(string $exportFormat = null): ProcessorConfig
    {
        $this->exportFormat = $exportFormat;

        return $this;
    }

    /**
     * @return string
     */
    public function getExportFormat(): string
    {
        return $this->exportFormat ?? $this->defaultFormat;
    }

    /**
     * @return string
     */
    public function getDefaultFormat(): string
    {
        return $this->defaultFormat;
    }

    /**
     * @param int $cacheTTL
     *
     * @return ProcessorConfig
     */
    public function setCacheTTL(int $cacheTTL): ProcessorConfig
    {
        $this->cacheTTL = $cacheTTL;

        return $this;
    }

    /**
     * @return int
     */
    public function getCacheTTL(): ?int
    {
        return $this->cacheTTL;
    }
}
