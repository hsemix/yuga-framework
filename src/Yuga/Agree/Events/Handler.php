<?php
namespace Yuga\EventHandlers;

interface Handler
{
    public function handle($event);
}