<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;

/**
 * "RW_SEARCH_FIELD_CONVERSION(FieldName, Column, Strategy)"
 *
 * SearchFieldConversion ::=
 *     "RW_SEARCH_FIELD_CONVERSION" "(" StringPrimary, StateFieldPathExpression "," [ integer | null ] ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlFieldConversion extends FunctionNode
{
    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    public $columnExpression;

    /**
     * @var integer|null
     */
    public $strategy;

    /**
     * {@inheritDoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        /** @var \Closure $whereBuilder */
        if (!($whereBuilder = $sqlWalker->getQuery()->getHint('rw_where_builder'))) {
            throw new \LogicException('Missing "rw_where_builder" hint for SearchFieldConversion.');
        }

        $whereBuilder = $whereBuilder();
        /** @var \Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder $whereBuilder */

        return $whereBuilder->getFieldConversionSql($this->fieldName, $this->columnExpression->dispatch($sqlWalker), null, $this->strategy);
    }

    /**
     * {@inheritDoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fieldName = $parser->Literal()->value;

        $parser->match(Lexer::T_COMMA);

        $this->columnExpression = $parser->StateFieldPathExpression();

        $lexer = $parser->getLexer();

        $parser->match(Lexer::T_COMMA);

        if ($lexer->isNextToken(Lexer::T_NULL)) {
            $parser->match(Lexer::T_NULL);
            $this->strategy = null;
        } else {
            $this->strategy = (int) $parser->Literal()->value;
        }

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
