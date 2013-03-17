<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Functional\Bundle\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\Bundle\RecordFilterBundle\Annotation as RecordFilter;

/**
 * Class User
 *
 * @ORM\MappedSupperClass
 */
class User
{
    /**
     * @ORM\Column(type="datetime")
     *
     * @RecordFilter\Field("user_id", type="number")
     */
    private $id;
}
