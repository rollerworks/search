<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

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
     * @RecordFilter\Field("customer_id", type="Rollerworks\RecordFilterBundle\Tests\Fixtures\CustomerType")
     * @RecordFilter\SqlConversion("Rollerworks\RecordFilterBundle\Tests\Fixtures\CustomerConversion")
     */
    private $id;
}
