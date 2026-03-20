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

use Carbon\Exceptions\Invalid_Cast_Exception;
use DateTimeInterface;
/**
 * Trait Cast.
 *
 * Utils to cast into an other class.
 */
trait Cast
{
    /**
     * Cast the current instance into the given class.
     *
     * @template T
     *
     * @param class-string<T> $className The $className::instance() method will be called to cast the current object.
     *
     * @return T
     */
    public function cast(string $class_name): mixed
    {
        if (!method_exists($class_name, 'instance')) {
            if (is_a($class_name, DateTimeInterface::class, true)) {
                return $class_name::create_from_format('U.u', $this->raw_format('U.u'))->set_timezone($this->get_timezone());
            }
            throw new Invalid_Cast_Exception("{$class_name} has not the instance() method needed to cast the date.");
        }
        return $class_name::instance($this);
    }
}