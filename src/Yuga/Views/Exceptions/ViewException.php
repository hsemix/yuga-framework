<?php

namespace Yuga\Views\Exceptions;

use Throwable;
use RuntimeException;

class ViewException extends RuntimeException
{
    protected int $viewLine;

    public function __construct(
        protected string $viewPath,
        protected string $compiledPath,
        Throwable $previous
    ) {
        $this->viewLine = $this->resolveViewLine($previous);

        parent::__construct(
            "Error rendering view [{$viewPath}:{$this->viewLine}]: " .
                $previous->getMessage(),
            (int) $previous->getCode(),
            $previous
        );
    }

    public function viewPath(): string
    {
        return $this->viewPath;
    }

    public function compiledPath(): string
    {
        return $this->compiledPath;
    }

    public function viewLine(): int
    {
        return $this->viewLine;
    }

    protected function resolveViewLine(Throwable $previous): int
    {
        if ($previous->getFile() === $this->compiledPath) {
            return $previous->getLine();
        }

        foreach ($previous->getTrace() as $frame) {
            if (($frame['file'] ?? null) === $this->compiledPath) {
                return (int) $frame['line'];
            }
        }

        return $previous->getLine();
    }
}
