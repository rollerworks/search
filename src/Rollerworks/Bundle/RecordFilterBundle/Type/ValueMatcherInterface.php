<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

/**
 * ValueMatcherInterface.
 *
 * An filter type can implement this to provide a regex-based matcher for the value.
 * This way the user is not required to 'always' use quotes when the value contains a dash or comma.
 *
 * Remember that this is only used for _matching_ not *validating*, keep the regex as simple as possible
 * and _never_ match more then needed!
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface ValueMatcherInterface
{
    /**
     * Returns the regex as a none-capturing group.
     *
     * The regex is used for matching a value in the list and detecting the end position when using a range.
     * So it should _always_ use none-capturing (?:), *especially* when using or '|', (?:regex1|regex2).
     *
     * In a list the regex is used as: {match-quoted}|{regex}-{regex}|{comparison-operator}?{regex}|[^,]+
     * You SHOULD NEVER match an (optional) comma and the end, as this will causes unexpected results.
     *
     * @return string|null
     *
     * @api
     */
    public function getMatcherRegex();
}
