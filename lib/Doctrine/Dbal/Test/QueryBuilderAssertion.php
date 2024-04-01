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

namespace Rollerworks\Component\Search\Doctrine\Dbal\Test;

use Doctrine\DBAL\Query\QueryBuilder;
use PHPUnit\Framework\Assert;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;

final class QueryBuilderAssertion
{
    /**
     * @phpstan-param array<string, mixed|array{0: mixed, 1: string}> $parameters by name with value or [value, type name)]
     */
    public static function assertQueryBuilderEquals(ConditionGenerator $generator, string $where, ?array $parameters = []): void
    {
        $queryBuilder = $generator->getQueryBuilder();
        $baseDql = $queryBuilder->getSQL();

        $generator->apply();

        $finalDql = $queryBuilder->getSQL();

        Assert::assertEquals($baseDql . $where, $finalDql);

        if ($parameters !== null) {
            self::assertQueryParametersEquals($parameters, $queryBuilder);
        }
    }

    public static function assertQueryParametersEquals(?array $parameters, QueryBuilder $qb): void
    {
        if ($parameters === null) {
            return;
        }

        $actualParameters = $qb->getParameters();

        foreach ($actualParameters as $name => $value) {
            $type = $qb->getParameterType($name);

            if ($type !== null) {
                $actualParameters[$name] = [$value, \is_object($type) ? $type->getName() : $type];
            } else {
                $actualParameters[$name] = $value;
            }
        }

        Assert::assertEquals($parameters, $actualParameters);
    }
}
