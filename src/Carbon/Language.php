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

use JsonSerializable;
class Language implements JsonSerializable, \Stringable
{
    protected static ?array $languages_names = null;
    protected static ?array $regions_names = null;
    protected string $id;
    protected string $code;
    protected ?string $variant = null;
    protected ?string $region = null;
    protected ?array $names = null;
    protected ?string $iso_name = null;
    protected ?string $native_name = null;
    public function __construct(string $id)
    {
        $this->id = str_replace('-', '_', $id);
        $parts = explode('_', $this->id);
        $this->code = $parts[0];
        if (isset($parts[1])) {
            if (!preg_match('/^[A-Z]+$/', $parts[1])) {
                $this->variant = $parts[1];
                $parts[1] = $parts[2] ?? null;
            }
            if ($parts[1]) {
                $this->region = $parts[1];
            }
        }
    }
    /**
     * Get the list of the known languages.
     */
    public static function all(): array
    {
        static::$languages_names ??= require __DIR__ . '/List/languages.php';
        return static::$languages_names;
    }
    /**
     * Get the list of the known regions.
     *
     * ⚠ ISO 3166-2 short name provided with no warranty, should not
     * be used for any purpose to show official state names.
     */
    public static function regions(): array
    {
        static::$regions_names ??= require __DIR__ . '/List/regions.php';
        return static::$regions_names;
    }
    /**
     * Get both isoName and nativeName as an array.
     */
    public function get_names(): array
    {
        $this->names ??= static::all()[$this->code] ?? ['isoName' => $this->code, 'nativeName' => $this->code];
        return $this->names;
    }
    /**
     * Returns the original locale ID.
     */
    public function get_id(): string
    {
        return $this->id;
    }
    /**
     * Returns the code of the locale "en"/"fr".
     */
    public function get_code(): string
    {
        return $this->code;
    }
    /**
     * Returns the variant code such as cyrl/latn.
     */
    public function get_variant(): ?string
    {
        return $this->variant;
    }
    /**
     * Returns the variant such as Cyrillic/Latin.
     */
    public function get_variant_name(): ?string
    {
        if ($this->variant === 'Latn') {
            return 'Latin';
        }
        if ($this->variant === 'Cyrl') {
            return 'Cyrillic';
        }
        return $this->variant;
    }
    /**
     * Returns the region part of the locale.
     */
    public function get_region(): ?string
    {
        return $this->region;
    }
    /**
     * Returns the region name for the current language.
     *
     * ⚠ ISO 3166-2 short name provided with no warranty, should not
     * be used for any purpose to show official state names.
     */
    public function get_region_name(): ?string
    {
        return $this->region ? static::regions()[$this->region] ?? $this->region : null;
    }
    /**
     * Returns the long ISO language name.
     */
    public function get_full_iso_name(): string
    {
        $this->iso_name ??= $this->get_names()['isoName'];
        return $this->iso_name;
    }
    /**
     * Set the ISO language name.
     */
    public function set_iso_name(string $iso_name): static
    {
        $this->iso_name = $iso_name;
        return $this;
    }
    /**
     * Return the full name of the language in this language.
     */
    public function get_full_native_name(): string
    {
        $this->native_name ??= $this->get_names()['nativeName'];
        return $this->native_name;
    }
    /**
     * Set the name of the language in this language.
     */
    public function set_native_name(string $native_name): static
    {
        $this->native_name = $native_name;
        return $this;
    }
    /**
     * Returns the short ISO language name.
     */
    public function get_iso_name(): string
    {
        $name = $this->get_full_iso_name();
        return trim(strstr($name, ',', true) ?: $name);
    }
    /**
     * Get the short name of the language in this language.
     */
    public function get_native_name(): string
    {
        $name = $this->get_full_native_name();
        return trim(strstr($name, ',', true) ?: $name);
    }
    /**
     * Get a string with short ISO name, region in parentheses if applicable, variant in parentheses if applicable.
     */
    public function get_iso_description(): string
    {
        $region = $this->get_region_name();
        $variant = $this->get_variant_name();
        return $this->get_iso_name() . ($region ? ' (' . $region . ')' : '') . ($variant ? ' (' . $variant . ')' : '');
    }
    /**
     * Get a string with short native name, region in parentheses if applicable, variant in parentheses if applicable.
     */
    public function get_native_description(): string
    {
        $region = $this->get_region_name();
        $variant = $this->get_variant_name();
        return $this->get_native_name() . ($region ? ' (' . $region . ')' : '') . ($variant ? ' (' . $variant . ')' : '');
    }
    /**
     * Get a string with long ISO name, region in parentheses if applicable, variant in parentheses if applicable.
     */
    public function get_full_iso_description(): string
    {
        $region = $this->get_region_name();
        $variant = $this->get_variant_name();
        return $this->get_full_iso_name() . ($region ? ' (' . $region . ')' : '') . ($variant ? ' (' . $variant . ')' : '');
    }
    /**
     * Get a string with long native name, region in parentheses if applicable, variant in parentheses if applicable.
     */
    public function get_full_native_description(): string
    {
        $region = $this->get_region_name();
        $variant = $this->get_variant_name();
        return $this->get_full_native_name() . ($region ? ' (' . $region . ')' : '') . ($variant ? ' (' . $variant . ')' : '');
    }
    /**
     * Returns the original locale ID.
     */
    public function __toString(): string
    {
        return $this->get_id();
    }
    /**
     * Get a string with short ISO name, region in parentheses if applicable, variant in parentheses if applicable.
     */
    public function jsonSerialize(): string
    {
        return $this->get_iso_description();
    }
}