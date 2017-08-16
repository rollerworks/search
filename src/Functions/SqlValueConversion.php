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

namespace Rollerworks\Component\Search\Doctrine\Orm\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Rollerworks\Component\Search\Doctrine\Orm\ConversionHintTrait;

/**
 * "RW_SEARCH_VALUE_CONVERSION(FieldMame, Column, Value, Strategy)".
 *
 * SearchValueConversion ::=
 *     "RW_SEARCH_VALUE_CONVERSION" "(" Literal, ScalarExpression,
 *      Literal "," Literal ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlValueConversion extends FunctionNode
{
    use ConversionHintTrait;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * PathExpression or SqlFieldConversion.
     *
     * @var \Doctrine\ORM\Query\AST\Node
     */
    private $column;

    /**
     * @var int
     */
    private $valueIndex;

    /**
     * @var int|string
     */
    private $strategy;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        $this->loadConversionHints($sqlWalker);

        return $this->nativePlatform->convertSqlValue(
            $this->parameters[$this->valueIndex],
            $this->fields[$this->fieldName],
            $this->column->dispatch($sqlWalker),
            $this->strategy
        );
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fieldName = $parser->Literal()->value;
        $parser->match(Lexer::T_COMMA);
        $this->column = $parser->ScalarExpression();
        $parser->match(Lexer::T_COMMA);
        $this->valueIndex = (int) $parser->Literal()->value;
        $parser->match(Lexer::T_COMMA);
        $this->strategy = $parser->Literal()->value;

        if (ctype_digit((string) $this->strategy)) {
            $this->strategy = (int) $this->strategy;
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
