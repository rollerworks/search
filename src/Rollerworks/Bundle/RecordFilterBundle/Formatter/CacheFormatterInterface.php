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

/**
 * CacheFormatterInterface.
 *
 * Cache able formatter interface.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface CacheFormatterInterface extends FormatterInterface
{
    /**
     * Returns the caching key of the formatted result.
     *
     * This should be called after the actual formatting.
     *
     * @return string
     */
    public function getCacheKey();
}
