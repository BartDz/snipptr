<?php

namespace Snipptr\Constants;

use ReflectionClass;

abstract class Constants
{
    public static function getConstants()
    {
        return array_values((new ReflectionClass(static::class))->getConstants());
    }
}