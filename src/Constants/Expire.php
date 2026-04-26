<?php

namespace Snipptr\Constants;

class Expire extends Constants
{
    public const NEVER = 'never';
    public const ONE_HOUR = '1h';
    public const ONE_DAY = '24h';
    public const ONE_WEEK = '7d';

    public static function getSelectFieldOptions(): array
    {
        return [
            self::NEVER => 'Never',
            self::ONE_HOUR => '1 hour',
            self::ONE_DAY => '24 hours',
            self::ONE_WEEK => '7 days',
        ];
    }
}