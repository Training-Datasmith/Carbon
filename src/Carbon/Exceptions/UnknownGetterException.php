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

use InvalidArgumentException as BaseInvalidArgumentException;
use Throwable;

class UnknownGetterException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string         $getter   getter name
     * @param int            $code
     */
    public function __construct(/**
     * The getter.
     */
        protected $getter,
        $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct("Unknown getter '{$this->getter}'", $code, $previous);
    }

    /**
     * Get the getter.
     */
    public function getGetter(): string
    {
        return $this->getter;
    }
}
