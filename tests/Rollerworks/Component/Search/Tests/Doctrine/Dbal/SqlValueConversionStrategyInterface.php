<?php

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;

interface SqlValueConversionStrategyInterface extends ConversionStrategyInterface, SqlValueConversionInterface
{
}
