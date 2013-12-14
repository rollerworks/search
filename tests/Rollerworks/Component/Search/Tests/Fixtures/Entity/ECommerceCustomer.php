<?php

namespace Rollerworks\Component\Search\Tests\Fixtures\Entity;

/**
 * @Entity
 * @Table(name="customers")
 */
class ECommerceCustomer
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
}
