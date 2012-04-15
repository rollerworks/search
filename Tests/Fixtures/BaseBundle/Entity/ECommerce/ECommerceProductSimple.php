<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Product
 *
 * @RecordFilter\Field("id")
 */

/**
 * ECommerce-Product
 *
 * @ORM\Entity
 * @ORM\Table(name="products")
 */
class ECommerceProductSimple
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @RecordFilter\Field("product_id")
     */
    private $id;
}