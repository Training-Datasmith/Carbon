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

use Carbon\Exceptions\Immutable_Exception;
use Symfony\Component\Config\Config_Cache_Factory_Interface;
class Translator_Immutable extends Translator
{
    private bool $constructed = false;
    public function __construct()
    {
        $this->constructed = true;
    }
    /**
     * @codeCoverageIgnore
     */
    public function set_directories(array $directories): static
    {
        $this->disallow_mutation(__METHOD__);
        return parent::set_directories($directories);
    }
    public function set_locale($locale): void
    {
        $this->disallow_mutation(__METHOD__);
    }
    /**
     * @codeCoverageIgnore
     */
    public function set_messages(string $locale, array $messages): static
    {
        $this->disallow_mutation(__METHOD__);
        return parent::set_messages($locale, $messages);
    }
    /**
     * @codeCoverageIgnore
     */
    public function set_translations(array $messages): static
    {
        $this->disallow_mutation(__METHOD__);
        return parent::set_translations($messages);
    }
    /**
     * @codeCoverageIgnore
     */
    public function set_config_cache_factory(Config_Cache_Factory_Interface $config_cache_factory): void
    {
        $this->disallow_mutation(__METHOD__);
    }
    public function reset_messages(?string $locale = null): bool
    {
        $this->disallow_mutation(__METHOD__);
        return parent::reset_messages($locale);
    }
    /**
     * @codeCoverageIgnore
     */
    public function set_fallback_locales(array $locales): void
    {
        $this->disallow_mutation(__METHOD__);
    }
    private function disallow_mutation(string $method): void
    {
        if ($this->constructed) {
            throw new Immutable_Exception($method . ' not allowed on ' . static::class);
        }
    }
}