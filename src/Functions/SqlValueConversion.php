<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;

/**
 * "RW_SEARCH_VALUE_CONVERSION(FieldMame, :parameter, Strategy, IsValueEmbedded)"
 *
 * SearchValueConversion ::=
 *     "RW_SEARCH_VALUE_CONVERSION" "(" StringPrimary, StateFieldPathExpression,
 *      InParameter "," [ integer | null ] "," Literal ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlValueConversion extends FunctionNode
{
    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    public $column;

    /**
     * @var \Doctrine\ORM\Query\AST\InputParameter
     */
    public $valueExpression;

    /**
     * @var integer|null
     */
    public $strategy;

    /**
     * @var bool
     */
    public $isValueEmbedded;

    /**
     * {@inheritdoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        /** @var \Closure $whereBuilder */
        if (!($whereBuilder = $sqlWalker->getQuery()->getHint('rw_where_builder'))) {
            throw new \LogicException('Missing "rw_where_builder" hint for SearchValueConversion.');
        }

        $whereBuilder = $whereBuilder();
        /** @var \Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder $whereBuilder */

        if ($this->isValueEmbedded) {
            $value = $this->valueExpression->name;
        } else {
            $value = $this->valueExpression->dispatch($sqlWalker);
        }

        return $whereBuilder->getValueConversionSql(
            $this->fieldName,
            $sqlWalker->walkPathExpression($this->column),
            $value,
            null,
            $this->strategy,
            $this->isValueEmbedded
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

        $this->column = $parser->StateFieldPathExpression();

        $parser->match(Lexer::T_COMMA);

        $this->valueExpression = $parser->InParameter();

        $parser->match(Lexer::T_COMMA);
        $lexer = $parser->getLexer();

        if ($lexer->isNextToken(Lexer::T_NULL)) {
            $parser->match(Lexer::T_NULL);
            $this->strategy = null;
        } else {
            $this->strategy = (int) $parser->Literal()->value;
        }

        $parser->match(Lexer::T_COMMA);

        $this->isValueEmbedded = 'true' === strtolower($parser->Literal()->value) ? true : false;

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
