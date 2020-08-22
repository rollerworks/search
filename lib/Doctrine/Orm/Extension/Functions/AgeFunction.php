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

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * "SEARCH_CONVERSION_AGE" "(" StringPrimary ")".
 */
final class AgeFunction extends FunctionNode
{
    public $stringPrimary;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform()->getName();
        $expression = $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary);

        $convertMap = [];
        $convertMap['postgresql'] = "to_char(age(%1\$s), 'YYYY'::text)::integer";
        $convertMap['mysql'] = "(DATE_FORMAT(NOW(), '%%Y') - DATE_FORMAT(%1\$s, '%%Y') - (DATE_FORMAT(NOW(), '00-%%m-%%d') < DATE_FORMAT(%1\$s, '00-%%m-%%d')))";
        $convertMap['drizzle'] = $convertMap['mysql'];
        $convertMap['mssql'] = 'DATEDIFF(hour, %1$s, GETDATE())/8766';
        $convertMap['oracle'] = 'trunc((months_between(sysdate, (sysdate - %1$s)))/12)';
        $convertMap['mock'] = $convertMap['postgresql'];

        if (isset($convertMap[$platform])) {
            return sprintf($convertMap[$platform], $expression);
        }

        throw new \RuntimeException(
            sprintf('Unsupported platform "%s" for SEARCH_CONVERSION_AGE.', $platform)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
