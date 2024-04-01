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

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\AbstractQueryPlatform;
use Rollerworks\Component\Search\Value\ValueHolder;

class ConversionHints
{
    public const CONTEXT_RANGE_LOWER_BOUND = 'range.lower_bound';
    public const CONTEXT_RANGE_UPPER_BOUND = 'range.upper_bound';
    public const CONTEXT_SIMPLE_VALUE = 'simple_value';
    public const CONTEXT_COMPARISON = 'comparison';

    /**
     * @var QueryField
     */
    public $field;

    /**
     * @var Connection
     */
    public $connection;

    /**
     * @var string
     */
    public $column;

    /**
     * @var string
     */
    public $context;

    /**
     * @var mixed|ValueHolder
     */
    public $originalValue;

    /**
     * @var AbstractQueryPlatform
     */
    private $queryPlatform;

    public function __construct(AbstractQueryPlatform $queryPlatform)
    {
        $this->queryPlatform = $queryPlatform;
    }

    /**
     * Returns a parameter-name to reference a value.
     */
    public function createParamReferenceFor($value, string|Type|null $type = null): string
    {
        if (\is_object($type)) {
            $type = Type::lookupName($type);

            trigger_deprecation(
                'rollerworks/search-doctrine-dbal',
                'v2.0.0-BETA2',
                sprintf(
                    'passing a %s object is deprecated and will no longer be accepted in v3.0.0, pass the type actual name "%s" instead.',
                    Type::class,
                    $type
                )
            );
        }

        return $this->queryPlatform->createParamReferenceFor($value, $type);
    }

    /**
     * Returns the value that is currently being processed (in context).
     *
     * The $this->originalValue might return a value-holder or actual
     * processing value depending on the context.
     */
    public function getProcessingValue()
    {
        switch ($this->context) {
            case self::CONTEXT_SIMPLE_VALUE:
                return $this->originalValue;

            case self::CONTEXT_COMPARISON:
                return $this->originalValue->getValue();

            case self::CONTEXT_RANGE_LOWER_BOUND:
                return $this->originalValue->getLower();

            case self::CONTEXT_RANGE_UPPER_BOUND:
                return $this->originalValue->getUpper();
        }
    }
}
