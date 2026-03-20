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
namespace Carbon;

use DateTimeInterface;
interface Carbon_Converter_Interface
{
    public function convert_date(DateTimeInterface $date_time, bool $negated = false): Carbon_Interface;
}