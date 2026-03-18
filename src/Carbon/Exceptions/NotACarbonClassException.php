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

use Carbon\CarbonInterface;
use InvalidArgumentException as BaseInvalidArgumentException;
use Throwable;

class NotACarbonClassException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string         $className
     * @param int            $code
     */
    public function __construct(/**
     * The className.
     */
    protected $className, $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(\sprintf(
            'Given class does not implement %s: %s',
            CarbonInterface::class,
            $this->className,
        ), $code, $previous);
    }

    /**
     * Get the className.
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
