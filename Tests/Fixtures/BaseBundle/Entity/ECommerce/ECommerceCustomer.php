<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Rollerworks\Bundle\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Customer
 *
 * @Entity
 * @Table(name="customers")
 */
class ECommerceCustomer
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @RecordFilter\Field("customer_id", type="Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerType")
     * @RecordFilter\SqlConversion("Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerConversion")
     */
    private $id;
}
