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

use Carbon\Factory;
use Carbon\Factory_Immutable;
use Carbon\Wrapper_Clock;
use Closure;
/**
 * Remember the factory that was the current at the creation of the object.
 */
trait Local_Factory
{
    /**
     * The clock that generated the current instance (or FactoryImmutable::getDefaultInstance() if none)
     */
    private ?Wrapper_Clock $clock = null;
    public function get_clock(): ?Wrapper_Clock
    {
        return $this->clock;
    }
    private function init_local_factory(): void
    {
        $this->clock = Factory_Immutable::get_current_clock();
    }
    /**
     * Trigger the given action using the local factory of the object, so it will be transmitted
     * to any object also using this trait and calling initLocalFactory() in its constructor.
     *
     * @template T
     *
     * @param Closure(): T $action
     *
     * @return T
     */
    private function transmit_factory(Closure $action): mixed
    {
        $previous_clock = Factory_Immutable::get_current_clock();
        Factory_Immutable::set_current_clock($this->clock);
        try {
            return $action();
        } finally {
            Factory_Immutable::set_current_clock($previous_clock);
        }
    }
    private function get_factory(): Factory
    {
        return $this->get_clock()?->get_factory() ?? Factory_Immutable::get_default_instance();
    }
}