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

use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "SEARCH_CONVERSION_AGE" "(" StringPrimary ")".
 */
final class AgeFunction extends PlatformSpecificFunction
{
    public $stringPrimary;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $this->getPlatformName($sqlWalker->getConnection());
        $expression = $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary);

        $convertMap = [];
        $convertMap['pgsql'] = "to_char(age(%1\$s), 'YYYY'::text)::integer";
        $convertMap['mysql'] = "(DATE_FORMAT(NOW(), '%%Y') - DATE_FORMAT(%1\$s, '%%Y') - (DATE_FORMAT(NOW(), '00-%%m-%%d') < DATE_FORMAT(%1\$s, '00-%%m-%%d')))";
        $convertMap['mssql'] = 'DATEDIFF(hour, %1$s, GETDATE())/8766';
        $convertMap['oci'] = 'trunc((months_between(sysdate, (sysdate - %1$s)))/12)';
        $convertMap['mock'] = $convertMap['pgsql'];

        if (isset($convertMap[$platform])) {
            return \sprintf($convertMap[$platform], $expression);
        }

        throw new \RuntimeException(
            \sprintf('Unsupported platform "%s" for SEARCH_CONVERSION_AGE.', $platform)
        );
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
