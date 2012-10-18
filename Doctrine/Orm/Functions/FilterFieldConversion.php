<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Lexer;

use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\WhereBuilder;

/**
 * "FILTER_FIELD_CONVERSION(FieldMame, column)"
 *
 * FilterFieldConversion ::=
 *     "RECORD_FILTER_FIELD_CONVERSION" "(" StringPrimary, StateFieldPathExpression ")"
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterFieldConversion extends FunctionNode
{
    public $fieldName;
    public $columnExpression;

    public function getSql(SqlWalker $sqlWalker)
    {
        /** @var WhereBuilder $whereBuilder */
        if (!($whereBuilder = $sqlWalker->getQuery()->getHint('where_builder_conversions'))) {
            throw new \LogicException('Missing "where_builder_conversions" hint for FilterFieldConversion.');
        }

        return $whereBuilder->getFieldConversionSql(trim($this->fieldName->dispatch($sqlWalker), "'"), $this->columnExpression->dispatch($sqlWalker));
    }

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);

        $this->fieldName = $parser->StringPrimary();

        $parser->match(Lexer::T_COMMA);

        $this->columnExpression = $parser->StateFieldPathExpression();

        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
