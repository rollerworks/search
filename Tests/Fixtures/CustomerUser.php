<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures;

class CustomerUser
{
    private $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getCustomerId()
    {
        return $this->id;
    }
}
