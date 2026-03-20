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
namespace Carbon\Traits;

use Carbon\Exceptions\Invalid_Format_Exception;
use Carbon\Factory_Immutable;
use DateTimeZone;
use Return_Type_Will_Change;
use Throwable;
/**
 * Trait Serialization.
 *
 * Serialization and JSON stuff.
 *
 * Depends on the following properties:
 *
 * @property int $year
 * @property int $month
 * @property int $daysInMonth
 * @property int $quarter
 *
 * Depends on the following methods:
 *
 * @method string|static locale(string $locale = null, string ...$fallbackLocales)
 * @method string        toJSON()
 */
trait Serialization
{
    use Object_Initialisation;
    /**
     * List of key to use for dump/serialization.
     *
     * @var string[]
     */
    protected array $dump_properties = ['date', 'timezone_type', 'timezone'];
    /**
     * Locale to dump comes here before serialization.
     *
     * @var string|null
     */
    protected $dump_locale;
    /**
     * Embed date properties to dump in a dedicated variables so it won't overlap native
     * DateTime ones.
     *
     * @var array|null
     */
    protected $dump_date_properties;
    /**
     * Return a serialized string of the instance.
     */
    public function serialize(): string
    {
        return serialize($this);
    }
    /**
     * Create an instance from a serialized string.
     *
     * If $value is not from a trusted source, consider using the allowed_classes option to limit
     * the types of objects that can be built, for instance:
     *
     * @example
     * ```php
     * $object = Carbon::fromSerialized($value, ['allowed_classes' => [Carbon::class, CarbonImmutable::class]]);
     * ```
     *
     * @param \Stringable|string $value
     * @param array              $options example: ['allowed_classes' => [CarbonImmutable::class]]
     *
     * @throws InvalidFormatException
     */
    public static function from_serialized($value, array $options = []): static
    {
        if (!isset($options['allowed_classes'])) {
            $options['allowed_classes'] = [static::class, \DateTimeZone::class, \DateInterval::class, \DatePeriod::class];
        }
        $instance = @unserialize((string) $value, $options);
        if (!$instance instanceof static) {
            throw new Invalid_Format_Exception("Invalid serialized value: {$value}");
        }
        return $instance;
    }
    /**
     * The __set_state handler.
     *
     * @param string|array $dump
     */
    #[Return_Type_Will_Change]
    public static function __set_state($dump): static
    {
        if (\is_string($dump)) {
            return static::parse($dump);
        }
        /** @var \DateTimeInterface $date */
        $date = get_parent_class(static::class) && method_exists(parent::class, '__set_state') ? parent::__set_state((array) $dump) : (object) $dump;
        return static::instance($date);
    }
    /**
     * Returns the values to dump on serialize() called on.
     */
    public function __serialize(): array
    {
        // @codeCoverageIgnoreStart
        if (isset($this->timezone_type, $this->timezone, $this->date)) {
            return ['date' => $this->date, 'timezone_type' => $this->timezone_type, 'timezone' => $this->dump_timezone($this->timezone)];
        }
        // @codeCoverageIgnoreEnd
        $timezone = $this->get_timezone();
        $export = ['date' => $this->format('Y-m-d H:i:s.u'), 'timezone_type' => $timezone->get_type(), 'timezone' => $timezone->get_name()];
        // @codeCoverageIgnoreStart
        if (\extension_loaded('msgpack') && isset($this->constructed_object_id)) {
            $timezone = $this->timezone ?? null;
            $export['dumpDateProperties'] = ['date' => $this->format('Y-m-d H:i:s.u'), 'timezone' => $this->dump_timezone($timezone)];
        }
        // @codeCoverageIgnoreEnd
        if ($this->local_translator ?? null) {
            $export['dumpLocale'] = $this->locale ?? null;
        }
        return $export;
    }
    /**
     * Set locale if specified on unserialize() called.
     */
    public function __unserialize(array $data): void
    {
        // @codeCoverageIgnoreStart
        try {
            $this->__construct($data['date'] ?? null, $data['timezone'] ?? null);
        } catch (Throwable $exception) {
            if (!isset($data['dumpDateProperties']['date'], $data['dumpDateProperties']['timezone'])) {
                throw $exception;
            }
            try {
                // FatalError occurs when calling msgpack_unpack() in PHP 7.4 or later.
                ['date' => $date, 'timezone' => $timezone] = $data['dumpDateProperties'];
                $this->__construct($date, $timezone);
            } catch (Throwable) {
                throw $exception;
            }
        }
        // @codeCoverageIgnoreEnd
        if (isset($data['dumpLocale']) && \is_string($data['dumpLocale']) && preg_match('/^[a-zA-Z]{2,8}(?:[_\-][a-zA-Z0-9]{2,8})*$/', $data['dumpLocale'])) {
            $this->locale($data['dumpLocale']);
        }
    }
    /**
     * Prepare the object for JSON serialization.
     */
    public function jsonSerialize(): mixed
    {
        $serializer = $this->local_serializer ?? $this->get_factory()->get_settings()['toJsonFormat'] ?? null;
        if ($serializer) {
            return \is_string($serializer) ? $this->raw_format($serializer) : $serializer($this);
        }
        return $this->to_json();
    }
    /**
     * @deprecated To avoid conflict between different third-party libraries, static setters should not be used.
     *             You should rather transform Carbon object before the serialization.
     *
     * JSON serialize all Carbon instances using the given callback.
     */
    public static function serialize_using(string|callable|null $format): void
    {
        Factory_Immutable::get_default_instance()->serialize_using($format);
    }
    /**
     * Cleanup properties attached to the public scope of DateTime when a dump of the date is requested.
     * foreach ($date as $_) {}
     * serializer($date)
     * var_export($date)
     * get_object_vars($date)
     */
    public function cleanup_dump_properties(): self
    {
        // @codeCoverageIgnoreStart
        if (PHP_VERSION < 8.199999999999999) {
            foreach ($this->dump_properties as $property) {
                if (isset($this->{$property})) {
                    unset($this->{$property});
                }
            }
        }
        // @codeCoverageIgnoreEnd
        return $this;
    }
    /** @codeCoverageIgnore */
    private function dump_timezone(mixed $timezone): mixed
    {
        return $timezone instanceof DateTimeZone ? $timezone->get_name() : $timezone;
    }
}