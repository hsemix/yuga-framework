<?php

namespace Yuga\Logger\Handlers;

interface HandlerInterface
{
    public function handle(array $record): void;
}