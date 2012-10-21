<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Rollerworks\Bundle\RecordFilterBundle\Annotation as RecordFilter;

/**
 * @Entity
 * @Table(name="addresses")
 */
class ECommerceAddress
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @RecordFilter\Field("address_id", type="number", AcceptRanges=true)
     */
    private $id;

    /**
     * @Column(type="string", unique=true)
     * @RecordFilter\Field("address_label", label="address_name", type="text")
     */
    private $name;

    /**
     * @Column(type="string", unique=true)
     * @RecordFilter\Field("address_street", type="invoice_type")
     */
    private $street;
}
