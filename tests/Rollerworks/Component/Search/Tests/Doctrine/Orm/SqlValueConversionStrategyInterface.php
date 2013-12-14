<?php

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;

interface SqlValueConversionStrategyInterface extends ConversionStrategyInterface, SqlValueConversionInterface
{
}
