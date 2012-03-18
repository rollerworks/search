<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\TestBundle\Entity\ECommerce;

use \Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @RecordFilter\Field("id", req=true, type="number", AcceptRanges=true)
 * @RecordFilter\Field("name", req=false)
 */
class ECommerceProductRange
{
    private $id;

    public function __construct()
    {
    }
}