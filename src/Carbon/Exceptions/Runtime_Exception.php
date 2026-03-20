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
 * Thrown for unexpected runtime errors within Carbon.
 *
 * This covers errors that are not input-validation failures (those use
 * {@see InvalidArgumentException}) but instead indicate an unexpected
 * internal state, such as a failed locale loading or an unexpected
 * return value from a PHP date function.
 *
 * @since 2.0
 * @see   Exception The root Carbon exception marker interface.
 */
interface RuntimeException extends Exception
{
}
