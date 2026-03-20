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
namespace Carbon;

class Carbon_Period_Immutable extends Carbon_Period
{
    /**
     * Default date class of iteration items.
     *
     * @var string
     */
    protected const DEFAULT_DATE_CLASS = Carbon_Immutable::class;
    /**
     * Date class of iteration items.
     */
    protected string $date_class = Carbon_Immutable::class;
    /**
     * Prepare the instance to be set (self if mutable to be mutated,
     * copy if immutable to generate a new instance).
     */
    protected function copy_if_immutable(): static
    {
        return $this->constructed ? clone $this : $this;
    }
}