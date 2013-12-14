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
use Doctrine\ORM\Query\AST\InputParameter;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;
use Rollerworks\Component\Search\Doctrine\Dbal\SearchMatch;

/**
 * "RW_SEARCH_MATCH(Column, Pattern, MatchType, CaseInsensitive)"
 *
 * SearchValueMatch ::=
 *     "RW_SEARCH_MATCH" "(" StateFieldPathExpression "," InParameter "," Literal "," Literal ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValueMatch extends FunctionNode
{
    /**
     * @var \Doctrine\ORM\Query\AST\PathExpression
     */
    public $column;

    /**
     * @var InputParameter|\Doctrine\ORM\Query\AST\Literal
     */
    public $pattern;

    /**
     * @var string
     */
    public $matchType;

    /**
     * @var boolean
     */
    public $caseInsensitive;

    /**
     * {@inheritDoc}
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        /** @var \Closure $whereBuilder */
        if (!($whereBuilder = $sqlWalker->getQuery()->getHint('rw_where_builder'))) {
            throw new \LogicException('Missing "rw_where_builder" hint for SearchValueMatch.');
        }

        $whereBuilder = $whereBuilder();
        /** @var \Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder $whereBuilder */
        $connection = $whereBuilder->getEntityManager()->getConnection();

        $column = $sqlWalker->walkArithmeticPrimary($this->column);
        if ($this->pattern instanceof InputParameter) {
            $pattern = $whereBuilder->getParameter($this->pattern->name);
        } else {
            $pattern = $whereBuilder->getParameter($this->pattern->value);
        }

        $pattern = $connection->quote($pattern);

        // Because Doctrine always requires an operator we use a sub-query with CASE
        if ($this->matchType == 'regex') {
            $statement = SearchMatch::getMatchSqlRegex($column, $pattern, $this->caseInsensitive, false, $connection);
        } else {
            $statement = SearchMatch::getMatchSqlLike($column, $pattern, $this->caseInsensitive, false, $connection);
        }

        return "(CASE WHEN $statement THEN 1 ELSE 0 END)";
    }

    /**
     * {@inheritDoc}
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->column = $parser->ArithmeticPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->pattern = $parser->InPutParameter();

        $parser->match(Lexer::T_COMMA);

        $this->matchType = $parser->Literal()->value;

        $parser->match(Lexer::T_COMMA);

        $this->caseInsensitive = 'true' === strtolower($parser->Literal()->value) ? true : false;

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
