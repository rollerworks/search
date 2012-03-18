<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use \Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @RecordFilter\Field("id")
 * @RecordFilter\Field("name")
 */
class ECommerceProductTwo
{
    private $id;

    public function __construct()
    {
    }
}