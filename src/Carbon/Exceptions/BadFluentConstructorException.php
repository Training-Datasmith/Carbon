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

use BadMethodCallException as BaseBadMethodCallException;
use Throwable;

class BadFluentConstructorException extends BaseBadMethodCallException implements BadMethodCallException
{
    /**
     * Constructor.
     *
     * @param string         $method
     * @param int            $code
     */
    public function __construct(/**
     * The method.
     */
    protected $method, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(\sprintf("Unknown fluent constructor '%s'.", $this->method), $code, $previous);
    }

    /**
     * Get the method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
