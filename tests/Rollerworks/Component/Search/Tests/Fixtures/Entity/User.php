<?php

namespace Rollerworks\Component\Search\Tests\Fixtures\Entity;

/**
 * @Entity
 * @Table(name="customers")
 */
class User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="date")
     */
    private $birthday;
}
