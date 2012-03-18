<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use \Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @RecordFilter\Field("id", type="Number")
 * @RecordFilter\Field("event_date", type="DateTime")
 */
class ECommerceProductWithType
{
    private $id;

    public function __construct()
    {
    }
}