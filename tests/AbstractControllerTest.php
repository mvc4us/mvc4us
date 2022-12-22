<?php

declare(strict_types=1);

namespace Mvc4us\Tests;

use Mvc4us\Controller\AbstractController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AbstractControllerTest extends TestCase
{

    protected $abstractController;

    protected function setup(): void
    {
        $this->abstractController = new class() extends AbstractController {

            public function returnThis(): self
            {
                return $this;
            }

            public function handle(Request $request
            ): Response {
                return new  Response("hello testing");
            }
        };
    }

    public function testHas()
    {
    }
}

