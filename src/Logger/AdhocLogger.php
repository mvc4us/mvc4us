<?php
declare(strict_types=1);

namespace Mvc4us\Logger;

use Psr\Log\AbstractLogger;

/**
 * @internal
 */
class AdhocLogger extends AbstractLogger
{
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        error_log(sprintf('[%s] adhoc.%s: %s', date('Y-m-d H:i:s'), strtoupper($level), $message));
    }
}
