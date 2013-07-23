<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Rollerworks\Bundle\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Customer.
 *
 * @Entity
 * @Table(name="customers")
 */
class ECommerceCustomer2
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     *
     * @RecordFilter\Field("customer_id", type="customer_type")
     */
    private $id;
}
