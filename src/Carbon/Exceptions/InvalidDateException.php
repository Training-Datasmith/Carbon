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

class InvalidDateException extends BaseInvalidArgumentException implements InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string         $field
     * @param mixed          $value
     * @param int            $code
     */
    public function __construct(/**
     * The invalid field.
     */
        private $field, /**
     * The invalid value.
     */
        private $value,
        $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($this->field.' : '.$this->value.' is not a valid value.', $code, $previous);
    }

    /**
     * Get the invalid field.
     *
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * Get the invalid value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
