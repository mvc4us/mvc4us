<?php
declare(strict_types=1);

namespace Mvc4us\Logger;

use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Utils;
use Mvc4us\Config\Config;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LoggerConfig
{
    static private LoggerInterface $logger;

    public static function getInstance(): LoggerInterface
    {
        if (isset(self::$logger)) {
            return self::$logger;
        }
        if (!class_exists('Monolog\\Logger')) {
            self::$logger = new AdhocLogger();
            return self::$logger;
        }
        self::$logger = new Logger('app');
        self::$logger
            ->pushHandler(
                (new StreamHandler(
                    APP_DIR . '/var/log/app.log',
                    Config::get('log', 'level') ?? LogLevel::NOTICE
                ))->setFormatter(
                    (new LineFormatter(
                        format: "[%datetime%] %channel%.%level_name%: %message%\n %context% %extra%\n",
                        ignoreEmptyContextAndExtra: true
                    ))->includeStacktraces(true, function ($line) {
                        return preg_replace(
                            '/(#[0-9]+) ([^(^)]+)\(([0-9]+)\): (.+)/',
                            '$1 $4 in $2 on line $3',
                            $line
                        );
                    })
                )
            )
            ->pushProcessor(callback: function (LogRecord $record) {
                if (isset($record->context['exception'])) {
                    $context = $record->context;
                    $e = $context['exception'];
                    //unset($context['exception']);
                    $message = sprintf(
                        'Uncaught Exception %s: "%s" in %s on line %s',
                        Utils::getClass($e),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    );
                    return new LogRecord(
                        datetime: $record->datetime,
                        channel: $record->channel,
                        level: $record->level,
                        message: $message,
                        context: $context,
                        extra: $record->extra,
                        formatted: $record->formatted
                    );
                }
                return $record;
            });
        if (Config::get('log', 'registerErrors') ?? false) {
            ErrorHandler::register(self::$logger);
        }
        return self::$logger;
    }
}
