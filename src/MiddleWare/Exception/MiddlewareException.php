<?php
declare(strict_types=1);

namespace Mvc4us\MiddleWare\Exception;

class MiddlewareException extends \RuntimeException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct(message: 'Middlewares can not throw exception.', previous: $previous);
    }
}
