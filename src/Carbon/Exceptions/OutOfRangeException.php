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
namespace Carbon\Exceptions;

use InvalidArgumentException as BaseInvalidArgumentException;
use Throwable;
// This will extends OutOfRangeException instead of InvalidArgumentException since 3.0.0
// use OutOfRangeException as BaseOutOfRangeException;
class OutOfRangeException extends Base_Invalid_Argument_Exception implements InvalidArgumentException
{
    /**
     * Constructor.
     *
     * @param string         $unit
     * @param mixed          $min
     * @param mixed          $max
     * @param mixed          $value
     * @param int            $code
     */
    public function __construct(
        /**
         * The unit or name of the value.
         */
        private $unit,
        /**
         * The range minimum.
         */
        private $min,
        /**
         * The range maximum.
         */
        private $max,
        /**
         * The invalid value.
         */
        private $value,
        $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct("{$this->unit} must be between {$this->min} and {$this->max}, {$this->value} given", $code, $previous);
    }
    /**
     * @return mixed
     */
    public function get_max()
    {
        return $this->max;
    }
    /**
     * @return mixed
     */
    public function get_min()
    {
        return $this->min;
    }
    /**
     * @return mixed
     */
    public function get_unit()
    {
        return $this->unit;
    }
    /**
     * @return mixed
     */
    public function get_value()
    {
        return $this->value;
    }
}