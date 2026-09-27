<?php

namespace Yuga\Views\Tracy;

use Tracy\Debugger;

class HaxTracyIntegration
{
    protected static bool $registered = false;

    public static function register(): void
    {
        if (
            self::$registered ||
            !class_exists(Debugger::class)
        ) {
            return;
        }

        Debugger::getBlueScreen()->addPanel(
            new HaxViewPanel()
        );

        self::$registered = true;
    }
}