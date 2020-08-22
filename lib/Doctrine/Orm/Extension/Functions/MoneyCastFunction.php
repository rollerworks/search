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
 * "SEARCH_MONEY_AS_NUMERIC" "(" StringPrimary ", " Literal ")".
 */
final class MoneyCastFunction extends FunctionNode
{
    public $stringPrimary;

    /**
     * @var int
     */
    public $scale;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $expression = $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary);
        $scale = $this->scale;

        if (strpos($sqlWalker->getConnection()->getDatabasePlatform()->getName(), 'mysql') !== false) {
            $castType = "DECIMAL(10, {$scale})";
        } else {
            $castType = $sqlWalker->getConnection()->getDatabasePlatform()->getDecimalTypeDeclarationSQL(
                ['scale' => $scale]
            );
        }

        return sprintf('CAST(%s AS %s)', $expression, $castType);
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->scale = (int) $parser->Literal()->value;

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
