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

namespace Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions;

use Carbon\CarbonInterval;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\DateIntervalConversion;

/**
 * "SEARCH_CAST_INTERVAL" "(" string, boolean ")".
 */
final class CastIntervalFunction extends PlatformSpecificFunction
{
    public $intervalExpression;
    public $inverted;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $connection = $sqlWalker->getConnection();
        $platform = $this->getPlatformName($connection);
        $expression = $this->intervalExpression;

        if ($platform === 'pgsql' || $platform === 'mock') {
            return \sprintf(
                'NOW() %s CAST(%s AS interval)',
                $this->inverted ? '-' : '+',
                $connection->quote($expression)
            );
        }

        if ($platform === 'mysql') {
            $value = CarbonInterval::fromString($expression);
            $value->locale('en');

            if ($this->inverted) {
                $value->invert();
            }

            return \sprintf(
                'NOW() %s %s',
                $this->inverted ? '-' : '+',
                DateIntervalConversion::convertForMysql($value)
            );
        }

        throw new \RuntimeException(
            \sprintf('Unsupported platform "%s" for DateIntervalConversion.', $platform)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->intervalExpression = (string) $parser->Literal()->value;

        $parser->match(TokenType::T_COMMA);

        $this->inverted = $parser->Literal()->value === 'true';

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
