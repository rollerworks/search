<?php

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;

interface SqlFieldConversionStrategyInterface extends ConversionStrategyInterface, SqlFieldConversionInterface
{
}
