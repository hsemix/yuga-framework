<?php
namespace Yuga\Intefaces\Events;

interface Handler
{
    public function handle($event);
}