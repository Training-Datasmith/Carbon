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
 * Thrown when a non-existent or inaccessible method is called on a Carbon object.
 *
 * Carbon supports dynamic methods via the macro/mixin system and magic `__call`
 * proxy. This exception is raised when neither the real method, a registered
 * macro, nor any mixin provides the called method name.
 *
 * ```php
 * try {
 *     Carbon::now()->nonExistentMethod();
 * } catch (Carbon\Exceptions\BadMethodCallException $e) {
 *     // method 'nonExistentMethod' does not exist
 * }
 * ```
 *
 * @since 2.0
 * @see   Exception The root Carbon exception marker interface.
 */
interface BadMethodCallException extends Exception
{
}
