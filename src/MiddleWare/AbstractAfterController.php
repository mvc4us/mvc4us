<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare;

abstract class AbstractAfterController implements AfterControllerInterface
{
    use MiddlewareTrait;
}
