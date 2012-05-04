<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\TestBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice
 *
 * @ORM\Entity
 * @ORM\Table(name="invoices")
 */
class ECommerceInvoice
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @RecordFilter\Field("id", req=true, type="number", AcceptRanges=true, AcceptCompares=true)
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @RecordFilter\Field("label", type="Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType")
     */
    private $name;

    public function __construct()
    {
    }
}
