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
 * "SEARCH_COUNT_CHILDREN" "(" StringPrimary "," StringPrimary "," StringPrimary ")".
 */
final class CountChildrenFunction extends FunctionNode
{
    public $stringPrimary;
    public $field;
    public $column;

    public function getSql(SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform()->getName();

        $table = $sqlWalker->walkSimpleArithmeticExpression($this->stringPrimary);
        $field = $sqlWalker->walkSimpleArithmeticExpression($this->field);
        $column = $sqlWalker->walkSimpleArithmeticExpression($this->column);

        return '(SELECT COUNT(*) FROM '.$table.' WHERE '.$field." = $column)";
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->stringPrimary = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->field = $parser->StringPrimary();
        $parser->match(Lexer::T_COMMA);

        $this->column = $parser->StringPrimary();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
