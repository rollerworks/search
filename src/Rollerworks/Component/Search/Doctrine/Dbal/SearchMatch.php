<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as DBALType;

/**
 * SearchMatch.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchMatch
{
    /**
     * Keep track of the connection state (SQLite only).
     *
     * This is used for SQLite to only register the custom function once.
     *
     * @var array
     */
    protected static $connectionState = array();

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
            $value = str_replace($char, '\\' . $char, $value);
        }

        return $value;
    }

    /**
     * Returns the list of characters to escape (by driver-name).
     *
     * @param string $driver
     *
     * @return string
     */
    public static function getEscapeChars($driver)
    {
        if ('mssql' === $driver) {
            return '%_[\\';
        }

        return '%_\\';
    }

    /**
     * Returns the SQL for the match.
     *
     * @param string     $column
     * @param string     $value           Fully escaped value or parameter-name
     * @param boolean    $caseInsensitive Is the match case insensitive
     * @param boolean    $negative        Is the match negative (exclude)
     * @param Connection $connection      Connection of the statement
     *
     * @return string Example "Column LIKE '%foo' ESCAPE '\0'"
     *
     * @throws \RuntimeException
     */
    public static function getMatchSqlLike($column, $value, $caseInsensitive, $negative, Connection $connection)
    {
        if (!$caseInsensitive) {
            return $column . ($negative ? ' NOT' : '') . " LIKE $value ESCAPE '\\\\'";
        }

        switch ($connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
                return $column . ($negative ? ' NOT' : '') . "ILIKE $value ESCAPE '\\\\'";

            case 'mysql':
            case 'drizzle':
            case 'oracle':
            case 'mssql':
            case 'sqlite':
            case 'mock':
                return "LOWER($column) " . ($negative ? 'NOT ' : '') . "LIKE LOWER($value) ESCAPE '\\\\'";

            default:
                throw new \RuntimeException(sprintf('Unsupported platform "%s".', $connection->getDatabasePlatform()->getName()));
        }
    }

    /**
     * Returns the SQL for the match (regex).
     *
     * @param string     $column
     * @param string     $value           Fully escaped value or parameter-name
     * @param boolean    $caseInsensitive Is the match case insensitive
     * @param boolean    $negative        Is the match negative (exclude)
     * @param Connection $connection      Connection of the statement
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public static function getMatchSqlRegex($column, $value, $caseInsensitive, $negative, Connection $connection)
    {
        switch ($connection->getDatabasePlatform()->getName()) {
            case 'postgresql':
                return sprintf('%s %s~%s %s', $column, ($negative ? '!' : ''), ($caseInsensitive ? '*' : ''), $value);

            case 'mysql':
            case 'drizzle':
                return sprintf('%s%s%s REGEXP %s', $column, ($caseInsensitive ? 'BINARY ' : ''), ($negative ? ' NOT' : ''), $value);

            case 'oracle':
                return sprintf("REGEXP_LIKE(%s, %s, '%s')", $column, $value, ($caseInsensitive ? 'i' : 'c'));

            case 'mssql':
                throw new \RuntimeException("MSSQL currently does not support regex matching without the usage of a custom extension.\nBecause of this its not possible to support this.\nIf you have a workable solution let me know.");

            case 'mock':
                return sprintf("RW_REGEXP(%s, %s, '%s') = %d", $value, $column, ($caseInsensitive ? 'ui' : ''), ($negative ? '1' : '0'));

            // SQLite is a bit difficult, we must use a custom function
            // But we can only register this once.
            case 'sqlite':
                $conn = $connection->getWrappedConnection();
                $objHash = spl_object_hash($conn);
                if (!isset(self::$connectionState[$objHash])) {
                    $conn->sqliteCreateFunction('RW_REGEXP', function ($pattern, $string, $flags) {
                        if (preg_match('{' . $pattern . '}' . $flags, $string)) {
                            return 1;
                        }

                        return 0;
                    }, 3);

                    self::$connectionState[$objHash] = true;
                }

                return sprintf("RW_REGEXP(%s, %s, '%s') = %d", $value, $column, ($caseInsensitive ? 'ui' : ''), ($negative ? '1' : '0'));

            default:
                throw new \RuntimeException(sprintf('Unsupported platform "%s".', $connection->getDatabasePlatform()->getName()));
        }
    }
}
