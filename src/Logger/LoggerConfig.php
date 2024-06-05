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

/**
 * @internal
 */
class LoggerConfig
{
    private static LoggerInterface $instance;

    public static function load(string $projectPath, string $name): void
    {
        if (!class_exists('Monolog\Logger')) {
            self::$instance = new AdhocLogger();
            return;
        }
        self::$instance = (new Logger($name))->useMicrosecondTimestamps(Config::get('log', 'useMicrosecond') ?? true);
        self::$instance
            ->pushHandler(
                (new StreamHandler(
                    $projectPath . '/var/log/' . $name . '.log',
                    Config::get('log', 'level') ?? LogLevel::NOTICE
                ))->setFormatter(
                    (new LineFormatter(
                        format: "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
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
                $nl = (!empty($record->context) || !empty($record->extra)) ? "\n" : "";
                if (isset($record->context['exception'])) {
                    $e = $record->context['exception'];
                    //unset($context['exception']);
                    $message = sprintf(
                        '%s: "%s" in %s on line %s%s',
                        ($record->message ? $record->message . "\n" : '') . Utils::getClass($e),
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine(),
                        $nl
                    );
                } else {
                    $message = sprintf("%s%s", $record->message, $nl);
                }
                return new LogRecord(
                    datetime: $record->datetime,
                    channel: $record->channel,
                    level: $record->level,
                    message: $message,
                    context: $record->context,
                    extra: $record->extra,
                    formatted: $record->formatted
                );
            });
        if (Config::get('log', 'registerErrors') ?? false) {
            //ErrorHandler::register(self::$instance);
            $handler = new ErrorHandler(self::$instance);
            $handler->registerErrorHandler(handleOnlyReportedErrors: !Config::isDebug());
            $handler->registerExceptionHandler();
            $handler->registerFatalHandler();
        }
    }

    public static function getInstance(): LoggerInterface
    {
        return self::$instance;
    }
}
