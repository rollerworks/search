<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use \Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @RecordFilter\Field("id", req=true)
 * @RecordFilter\Field("name", req=false)
 */
class ECommerceProductReq
{
    private $id;

    public function __construct()
    {
    }
}