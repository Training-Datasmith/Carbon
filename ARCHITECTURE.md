# Carbon — Architecture

## Purpose

Carbon is a PHP library that extends PHP's built-in `DateTime` / `DateTimeImmutable` with a fluent, developer-friendly API. It supports arithmetic, formatting, localisation in 280+ languages, relative-time expressions, mocking, and integration with Laravel and Symfony.

## Directory Structure

```
src/Carbon/
  Constants/
    Format.php              — Interface of date format string constants (RFC7231, ISO, etc.)
  Exceptions/
    Exception.php           — Marker interface for all Carbon exceptions
    Bad_Method_Call_Exception.php  — Invalid magic method call
    Invalid_Argument_Exception.php — Bad argument passed to a Carbon method
    Runtime_Exception.php   — Unexpected runtime error
  Lang/                     — 280+ locale translation files (PHP arrays)
  List/
    languages.php           — Canonical locale → language name mapping
    regions.php             — Region code → region name mapping

  Carbon.php (via vendor/nesbot/carbon at runtime)
  CarbonImmutable.php
  CarbonInterval.php
  CarbonPeriod.php
  …
```

> Note: This repo contains the curated source subset. The full Carbon class hierarchy lives in the upstream `nesbot/carbon` package; this repo holds constants, exceptions, and locale data.

## Key Design Decisions

### Mutable vs. Immutable
`Carbon` extends `DateTime` (mutable). `CarbonImmutable` extends `DateTimeImmutable`. Both share behaviour through the `CarbonInterface` and `Date` traits. New code should prefer `CarbonImmutable`.

### Format Constants Interface
`Carbon\Constants\Format` is an interface (not a class) so that both `Carbon` and `CarbonImmutable` can implement it via `implements` without inheritance conflicts. Constants are accessed as `Format::RFC7231_FORMAT`.

### Exception Hierarchy
All exceptions implement `Carbon\Exceptions\Exception` (a marker interface). This allows callers to catch all Carbon errors with a single `catch (Carbon\Exceptions\Exception $e)` while preserving SPL exception type information.

### Locale Strings
Locale data is stored as PHP arrays (`Lang/xx.php`) rather than JSON or YAML for performance (opcode cache). Each file returns an associative array of translation keys to translated strings/patterns.

### Macro / Mixin System
Carbon supports runtime extension via static `macro()` and `mixin()` calls. Macros are closures bound to the Carbon instance, enabling plugins to add fluent methods without subclassing.

### Test Helpers (Time Mocking)
`Carbon::setTestNow()` installs a fixed "now" value. All `Carbon::now()` / `new Carbon()` calls respect this value, making deterministic time-based testing possible without touching system time.

## Extension Points

- **Custom macros**: `Carbon::macro('methodName', function() { … })`
- **Mixins**: `Carbon::mixin(new MyMixin())` — bulk-adds methods from a class
- **Custom locale**: add a `Lang/xx.php` file with the required translation keys
- **Custom format constants**: implement `Carbon\Constants\Format` in a subclass

## Dependency Flow

```
Application
  → Carbon::now() / new Carbon('2025-01-15')
       → Carbon extends DateTime
            → CarbonInterface (fluent methods: addDays, diffForHumans, format, …)
            → Translator (locale-aware relative strings)
            → CarbonPeriod / CarbonInterval (ranges and durations)
```
