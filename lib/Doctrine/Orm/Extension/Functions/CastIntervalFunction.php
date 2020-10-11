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
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\DateIntervalConversion;

/**
 * "SEARCH_CAST_INTERVAL" "(" string, boolean ")".
 */
final class CastIntervalFunction extends FunctionNode
{
    public $intervalExpression;
    public $inverted;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $connection = $sqlWalker->getConnection();
        $platform = $connection->getDatabasePlatform()->getName();
        $expression = $this->intervalExpression;

        if ($platform === 'postgresql' || $platform === 'mock') {
            return sprintf(
                'NOW() %s CAST(%s AS interval)',
                $this->inverted ? '-' : '+',
                $connection->quote($expression)
            );
        }

        if ($platform === 'mysql' || $platform === 'drizzle') {
            $value = CarbonInterval::fromString($expression);
            $value->locale('en');

            if ($this->inverted) {
                $value->invert();
            }

            return sprintf(
                'NOW() %s %s',
                $this->inverted ? '-' : '+',
                DateIntervalConversion::convertForMysql($value)
            );
        }

        throw new \RuntimeException(
            sprintf('Unsupported platform "%s" for DateIntervalConversion.', $platform)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->intervalExpression = (string) $parser->Literal()->value;

        $parser->match(Lexer::T_COMMA);

        $this->inverted = $parser->Literal()->value === 'true';

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
