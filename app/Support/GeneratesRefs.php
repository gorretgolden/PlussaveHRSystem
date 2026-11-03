<?php

namespace App\Support;

use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Reference numbers are user-facing and the column is unique, so allocation has to
 * survive two makers submitting in the same second.
 */
trait GeneratesRefs
{
    public static function nextRef(): string
    {
        $prefix = static::REF_PREFIX;

        $last = static::query()->lockForUpdate()->pluck('ref')
            ->map(fn ($ref) => (int) substr((string) $ref, strlen($prefix)))
            ->max();

        return $prefix.max(static::REF_MIN, (int) $last + 1);
    }

    public static function createWithRef(array $attributes): static
    {
        unset($attributes['ref']);

        foreach (range(1, 5) as $attempt) {
            try {
                return static::create($attributes + ['ref' => static::nextRef()]);
            } catch (UniqueConstraintViolationException $exception) {
                if ($attempt === 5) {
                    throw $exception;
                }

                usleep(random_int(2_000, 30_000));
            }
        }
    }
}
