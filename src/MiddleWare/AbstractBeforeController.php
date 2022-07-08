<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

abstract class AbstractBeforeController implements BeforeControllerInterface
{
    use MiddlewareTrait;
}
