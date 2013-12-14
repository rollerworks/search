<?php

namespace Rollerworks\Component\Search\Tests\Fixtures;

class CustomerId
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
