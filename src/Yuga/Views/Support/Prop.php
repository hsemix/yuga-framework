<?php

namespace Yuga\Views\Support;

final class Prop
{
    public static function required(): RequiredProp
    {
        return new RequiredProp();
    }
}
