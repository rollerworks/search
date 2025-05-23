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
 * "SEARCH_MONEY_AS_NUMERIC" "(" StringPrimary ", " Literal ")".
 */
final class MoneyCastFunction extends PlatformSpecificFunction
{
    public $stringPrimary;

    /**
     * @var int
     */
    public $scale;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $connection = $sqlWalker->getConnection();

        $expression = $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary);
        $scale = $this->scale;

        if ($this->getPlatformName($connection) === 'mysql') {
            $castType = "DECIMAL(10, {$scale})";
        } else {
            $castType = $connection->getDatabasePlatform()->getDecimalTypeDeclarationSQL(
                ['scale' => $scale, 'precision' => 10, 'name' => $expression]
            );
        }

        return \sprintf('CAST(%s AS %s)', $expression, $castType);
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->scale = (int) $parser->Literal()->value;

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }
}
