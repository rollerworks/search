<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Functional\Bundle\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DemoController extends Controller
{
    public function indexAction()
    {
        return new Response('Nothing to see here. move along.');
    }
}
