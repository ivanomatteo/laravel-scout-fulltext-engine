<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine;

use Illuminate\Support\Facades\Config;

class Pkg
{
    public static function getName(): string
    {
        return 'laravel-scout-fulltext-engine';
    }

    public static function getShortName(): string
    {
        return 'scout-fulltext-engine';
    }

    public static function configGet(string $key, mixed $default = null): mixed
    {
        return Config::get(static::configKey($key), $default);
    }

    public static function configSet(string $key, mixed $value): void
    {
        Config::set(static::configKey($key), $value);
    }

    public static function configKey(string $key): mixed
    {
        return static::getShortName() . ".$key";
    }
}
