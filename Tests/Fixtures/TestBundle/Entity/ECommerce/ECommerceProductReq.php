<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\TestBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @ORM\Entity
 * @ORM\Table(name="products")
 */
class ECommerceProductReq
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @RecordFilter\Field("id", req=true)
     */
    private $id;

    public function __construct()
    {
    }
}
