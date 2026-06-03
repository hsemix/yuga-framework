<?php

declare(strict_types=1);

namespace Yuga\Interfaces\Commands;

interface CommandInterface
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle();
}
