<?php

declare(strict_types=1);

namespace Mvc4us\Logger;

use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
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
                        format: "[%datetime%] %channel%.%level_name%: %message%%context.exception% %extra%" . PHP_EOL,
                        ignoreEmptyContextAndExtra: true
                    ))->includeStacktraces(true, function ($line) {
                        return preg_replace(
                            '/(#[0-9]+) ([^(^)]+)\(([0-9]+)\): (.+)/',
                            //'$1 $4 in $2 on line $3',
                            '$1 $4 at $2:$3',
                            $line
                        );
                    })
                )
            )
            ->pushProcessor(new PsrLogMessageProcessor())
            ->pushProcessor(callback: function (LogRecord $record) {
                $nl = (!empty($record->context) || !empty($record->extra)) ? PHP_EOL : " ";
                if (isset($record->context['exception'])) {
                    $e = $record->context['exception'];
                    //unset($context['exception']);
                    $message = sprintf(
                        '%s%s',
                        ($record->message ? $record->message . PHP_EOL : '') . Utils::getClass($e),
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
