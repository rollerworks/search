<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @ORM\Entity
 * @ORM\Table(name="products")
 */
class ECommerceProductWithType
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @RecordFilter\Field("id", req=true, type="Number")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @RecordFilter\Field("event_date", type="DateTime")
     */
    private $event_date;
}