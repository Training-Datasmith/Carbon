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
use Symfony\Component\Translation\Pluralization_Rules;
// @codeCoverageIgnoreStart
if (class_exists(Pluralization_Rules::class)) {
    Pluralization_Rules::set(static fn($number) => Pluralization_Rules::get($number, 'ca'), 'ca_ES_Valencia');
}
// @codeCoverageIgnoreEnd
return array_replace_recursive(require __DIR__ . '/ca.php', []);