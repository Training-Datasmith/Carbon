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
 * Marker interface for all Carbon exceptions.
 *
 * All exceptions thrown by Carbon implement this interface, allowing callers
 * to catch any Carbon-related error with a single catch block:
 *
 * ```php
 * try {
 *     Carbon::parse($input);
 * } catch (Carbon\Exceptions\Exception $e) {
 *     // Handle any Carbon error
 * }
 * ```
 *
 * The concrete exception classes also extend the appropriate SPL exception,
 * so they can be caught as either a Carbon exception or an SPL exception.
 *
 * @since 2.0
 */
interface Exception
{
}
