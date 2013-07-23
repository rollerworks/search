<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Formatter;

use Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Input\InputInterface;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Doctrine\Common\Cache\Cache;

/**
 * Handles the caching of an Formatter.
 *
 * Checks the cache if there is en result present, if not it
 * delegates the formatting to the parent and caches the result.
 *
 * This class provides an caching layer for the formatter.
 * The caching key is computed from the FieldSet name,
 * fields (there type class-name and current options).
 *
 * You can get any failure messages for the parent formatter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheFormatter implements CacheFormatterInterface
{
    /**
     * @var FormatterInterface
     */
    protected $formatter;

    /**
     * @var FieldSet|null
     */
    protected $fieldSet;

    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * @var integer
     */
    protected $cacheLifeTime;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var boolean
     */
    protected $formatted = false;

    /**
     * @var array
     */
    protected $filters = array();

    /**
     * Constructor.
     *
     * @param Cache   $cacheProvider
     * @param integer $lifeTime
     */
    public function __construct(Cache $cacheProvider, $lifeTime = 0)
    {
        $this->cacheDriver = $cacheProvider;
        $this->cacheLifeTime = (int) $lifeTime;
    }

    /**
     * Set the parent formatter.
     *
     * @param FormatterInterface $parentFormatter
     *
     * @api
     */
    public function setFormatter(FormatterInterface $parentFormatter)
    {
        $this->formatter = $parentFormatter;
        $this->filters   = array();
        $this->formatted = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        if (!$this->formatted) {
            throw new \RuntimeException('formatInput() must be executed before calling this function.');
        }

        return $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldSet()
    {
        return $this->fieldSet;
    }

    /**
     * {@inheritdoc}
     *
     * @param FormatterInterface $formatter
     * @param null|string        $cachePrefix An optional caching prefix for preventing false positives
     *
     * @throws \RuntimeException
     */
    public function formatInput(InputInterface $input, FormatterInterface $formatter = null, $cachePrefix = null)
    {
        $this->fieldSet  = $input->getFieldSet();
        $this->cacheKey  = 'formatter_' . $cachePrefix . md5($input->getHash() . $this->generateCacheKey($this->fieldSet));
        $this->filters   = array();
        $this->formatted = false;

        if ($this->cacheDriver->contains($this->cacheKey)) {
            $this->formatted = true;
            $this->filters = $this->cacheDriver->fetch($this->cacheKey);

            return true;
        }

        if (!$formatter) {
            $formatter = $this->formatter;
        }

        if (!$formatter) {
            throw new \RuntimeException('There is no result in the cache and no formatter is set for delegating.');
        }

        if (!$formatter->formatInput($input)) {
            return false;
        }

        $this->formatted = true;
        $this->filters = $formatter->getFilters();
        $this->cacheDriver->save($this->cacheKey, $this->filters, $this->cacheLifeTime);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheKey()
    {
        return $this->cacheKey;
    }

    /**
     * Generates and returns the cache key from the FieldSet.
     *
     * @param FieldSet $fieldSet
     *
     * @return string
     */
    protected function generateCacheKey(FieldSet $fieldSet)
    {
        $key = $fieldSet->getSetName();
        foreach ($fieldSet->all() as $fieldName => $field) {
            $key .= $fieldName;
            $key .= ($field->acceptRanges() ? 1 : 0);
            $key .= ($field->acceptCompares() ? 1 : 0);

            if (($type = $field->getType()) && is_object($type)) {
                $key .= get_class($type);

                if ($type instanceof ConfigurableTypeInterface) {
                    $key .= serialize($type->getOptions());
                }
            }
        }

        return md5($key);
    }
}
