<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Type;

/**
 * ValueMatcherInterface.
 *
 * An filter type can implement this to provide an regex-based matcher for the value.
 * This way the user is not required to 'always' use quotes when the value contains a dash or comma.
 *
 * Remember that this is only for __matching__ not ***validating***, make the regex as simple as possible.
 * And __never__ match more then necessary!
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface ValueMatcherInterface
{
    /**
     * Returns the regex as none-capturing group.
     *
     * The regex is used for matching an value in the list and detecting the end position when using an range.
     * So it should __always__ use none-capturing (?:), ***especially*** when using or '|', (?:regex1|regex2).
     *
     * In an list the regex is used as: {match-quoted}|{regex}-{regex}|{comparison-operator}?{regex}|[^,]+
     * You SHOULD NOT match an (optional) comma and the end, since this will cause unexpected results.
     *
     * @return string
     *
     * @api
     */
    public function getMatcherRegex();
}
