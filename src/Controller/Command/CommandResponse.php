<?php
declare(strict_types=1);

namespace Mvc4us\Controller\Command;

use Symfony\Component\HttpFoundation\Response;

class CommandResponse extends Response
{
    protected int $exitCode;

    public static function fromResponse(?Response $response): CommandResponse
    {
        if ($response instanceof CommandResponse) {
            return $response;
        }
        return new CommandResponse($response?->getContent() ?? '');
    }

    public function __construct(?string $content = '', int $exitCode = 0)
    {
        $this->setExitCode($exitCode);
        parent::__construct($content);
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function setExitCode(int $exitCode): static
    {
        if ($exitCode < 0 || $exitCode > 255) {
            throw new \InvalidArgumentException('Invalid (POSIX) exit code: ' . $exitCode);
        }
        $this->exitCode = $exitCode;
        return $this->setStatusCode($exitCode === 0 ? 200 : 500);
    }

    public function isError(): bool
    {
        return $this->exitCode > 0;
    }
}
