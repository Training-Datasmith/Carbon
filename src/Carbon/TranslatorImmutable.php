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

namespace Carbon;

use Carbon\Exceptions\ImmutableException;
use Symfony\Component\Config\ConfigCacheFactoryInterface;

class TranslatorImmutable extends Translator
{
    private bool $constructed = false;

    public function __construct()
    {
        $this->constructed = true;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setDirectories(array $directories): static
    {
        $this->disallowMutation(__METHOD__);

        return parent::setDirectories($directories);
    }

    public function setLocale($locale): void
    {
        $this->disallowMutation(__METHOD__);
    }

    /**
     * @codeCoverageIgnore
     */
    public function setMessages(string $locale, array $messages): static
    {
        $this->disallowMutation(__METHOD__);

        return parent::setMessages($locale, $messages);
    }

    /**
     * @codeCoverageIgnore
     */
    public function setTranslations(array $messages): static
    {
        $this->disallowMutation(__METHOD__);

        return parent::setTranslations($messages);
    }

    /**
     * @codeCoverageIgnore
     */
    public function setConfigCacheFactory(ConfigCacheFactoryInterface $configCacheFactory): void
    {
        $this->disallowMutation(__METHOD__);
    }

    public function resetMessages(?string $locale = null): bool
    {
        $this->disallowMutation(__METHOD__);

        return parent::resetMessages($locale);
    }

    /**
     * @codeCoverageIgnore
     */
    public function setFallbackLocales(array $locales): void
    {
        $this->disallowMutation(__METHOD__);
    }

    private function disallowMutation(string $method): void
    {
        if ($this->constructed) {
            throw new ImmutableException($method.' not allowed on '.static::class);
        }
    }
}
