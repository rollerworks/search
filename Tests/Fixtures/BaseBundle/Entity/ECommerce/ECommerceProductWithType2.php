<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use \Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @RecordFilter\Field("id", type="Number")
 * @RecordFilter\Field("event_date", type="DateTime", _time_optional=true)
 */
class ECommerceProductWithType2
{
    private $id;

    public function __construct()
    {
    }
}