<?php

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
 * "RW_SEARCH_FIELD_CONVERSION(FieldName, Column, Strategy)".
 *
 * SearchFieldConversion ::=
 *     "RW_SEARCH_FIELD_CONVERSION" "(" StringPrimary, StateFieldPathExpression "," [ Integer ] ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlFieldConversion extends FunctionNode
{
    use ConversionHintTrait;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    private $columnExpression;

    /**
     * @var int|null
     */
    private $strategy;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        $this->loadConversionHints($sqlWalker);

        return $this->nativePlatform->getFieldColumn(
            $this->fieldName,
            $this->strategy,
            $this->columnExpression->dispatch($sqlWalker)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fieldName = $parser->Literal()->value;
        $parser->match(Lexer::T_COMMA);
        $this->columnExpression = $parser->StateFieldPathExpression();
        $parser->match(Lexer::T_COMMA);
        $this->strategy = (int) $parser->Literal()->value;

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
