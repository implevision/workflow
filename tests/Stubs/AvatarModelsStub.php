<?php

namespace Avatar\Infrastructure\Models\Api\v1;

/**
 * Stub for TbPolicy Eloquent model used in tests.
 */
class TbPolicy
{
    /** @var array<int|string, object|null> Keyed by id, set per-test. */
    public static array $findMap = [];

    public static function find($id): ?object
    {
        return static::$findMap[$id] ?? null;
    }

    public static function reset(): void
    {
        static::$findMap = [];
    }
}

/**
 * Stub for TbProduct Eloquent model used in tests.
 */
class TbProduct
{
    /** @var array<int|string, object|null> Keyed by id, set per-test. */
    public static array $findMap = [];

    public static function find($id): ?object
    {
        return static::$findMap[$id] ?? null;
    }

    public static function reset(): void
    {
        static::$findMap = [];
    }
}
