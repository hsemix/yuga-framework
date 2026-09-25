<?php

namespace Yuga\Views\Exceptions;

use Throwable;
use RuntimeException;

class ViewException extends RuntimeException
{
    public function __construct(
        protected string $viewPath,
        protected string $compiledPath,
        Throwable $previous
    ) {
        parent::__construct(
            "Error rendering view [{$viewPath}]: " . $previous->getMessage(),
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
}
