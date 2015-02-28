<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal\Query;

use Doctrine\DBAL\Connection;

/**
 * SearchMatch is utility class for pattern-matcher searching with Doctrine DBAL.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @internal
 */
final class SearchMatch
{
    /**
     * Escapes the value for usage in a LIKE statement.
     *
     * Basically this removes the NULL-chars
     * and escapes special characters with a NULL char.
     *
     * @param string $value
     * @param string $chars List of characters to escape
     *
     * @return string
     */
    public static function escapeValue($value, $chars = '%_\\')
    {
        $chars = str_split($chars);

        foreach ($chars as $char) {
            $value = str_replace($char, '\\'.$char, $value);
        }

        return $value;
    }

    /**
     * Returns the list of characters to escape (by driver-name).
     *
     * @param string $platform
     *
     * @return string
     */
    public static function getEscapeChars($platform)
    {
        if ('mssql' === $platform) {
            return '%_[\\';
        }

        return '%_\\';
    }

    /**
     * Returns the SQL for the match.
     *
     * @param string     $column
     * @param string     $value           Fully escaped value or parameter-name
     * @param bool       $caseInsensitive Is the match case insensitive
     * @param bool       $negative        Is the match negative (exclude)
     * @param Connection $connection      Connection of the statement
     *
     * @return string Example "Column LIKE '%foo' ESCAPE '\0'"
     *
     * @throws \RuntimeException
     */
    public static function getMatchSqlLike($column, $value, $caseInsensitive, $negative, Connection $connection)
    {
        $excluding = ($negative ? ' NOT' : '');
        $escape = $connection->quote('\\');

        if ($caseInsensitive) {
            return "LOWER($column)".$excluding." LIKE LOWER($value) ESCAPE $escape";
        }

        return $column.$excluding." LIKE $value ESCAPE $escape";
    }

    /**
     * Returns the SQL for the match (regex).
     *
     * @param string     $column
     * @param string     $value           Fully escaped value or parameter-name
     * @param bool       $caseInsensitive Is the match case insensitive
     * @param bool       $negative        Is the match negative (exclude)
     * @param Connection $connection      Connection of the statement
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function getMatchSqlRegex($column, $value, $caseInsensitive, $negative, Connection $connection)
    {
        $platform = $connection->getDatabasePlatform()->getName();

        $formatMap = array();
        $formatMap['postgresql'] = array('%s ~ %s', '%s ~* %s');
        $formatMap['mysql'] = array('%s REGEXP %s', '%s REGEXP BINARY %s');
        $formatMap['drizzle'] = $formatMap['mysql'];
        $formatMap['oracle'] = array("REGEXP_LIKE(%s, %s, 'c')", "REGEXP_LIKE(%s, %s, 'i')");
        $formatMap['sqlite'] = array("RW_REGEXP(%2\$s, %1\$s, 'u')", "RW_REGEXP(%2\$s, %1\$s, 'ui')");
        $formatMap['mock'] = $formatMap['sqlite'];

        if (isset($formatMap[$platform])) {
            return ($negative ? 'NOT ' : '').sprintf(
                $formatMap[$platform][(int) $caseInsensitive],
                $column,
                $value
            );
        }

        throw new \RuntimeException(
            sprintf('Unsupported platform "%s" for Regex pattern.', $platform)
        );
    }
}
