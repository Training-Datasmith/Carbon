<?php

declare (strict_types=1);
/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Carbon\Traits;

use Carbon\Carbon;
use Carbon\Carbon_Immutable;
/**
 * Trait Mutability.
 *
 * Utils to know if the current object is mutable or immutable and convert it.
 */
trait Mutability
{
    use Cast;
    /**
     * Returns true if the current class/instance is mutable.
     */
    public static function is_mutable(): bool
    {
        return false;
    }
    /**
     * Returns true if the current class/instance is immutable.
     */
    public static function is_immutable(): bool
    {
        return !static::is_mutable();
    }
    /**
     * Return a mutable copy of the instance.
     */
    public function to_mutable(): Carbon
    {
        return $this->cast(Carbon::class);
    }
    /**
     * Return an immutable copy of the instance.
     */
    public function to_immutable(): Carbon_Immutable
    {
        // Immutable objects are fine as is (uncloned)
        if ($this::class === Carbon_Immutable::class) {
            return $this;
        }
        return $this->cast(Carbon_Immutable::class);
    }
}