<?php

declare(strict_types=1);

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Carbon\Exceptions;

/**
 * Thrown when an invalid argument is passed to a Carbon method.
 *
 * Common causes:
 * - An unrecognised date format string passed to `Carbon::createFromFormat()`
 * - An invalid timezone identifier passed to `Carbon::setTimezone()`
 * - A negative count passed to `CarbonInterval`
 *
 * Concrete classes implementing this interface should also extend
 * `\InvalidArgumentException` so they can be caught as either:
 *
 * ```php
 * catch (Carbon\Exceptions\InvalidArgumentException $e) { … }
 * catch (\InvalidArgumentException $e) { … }
 * ```
 *
 * @since 2.0
 * @see   Exception The root Carbon exception marker interface.
 */
interface InvalidArgumentException extends Exception
{
}
