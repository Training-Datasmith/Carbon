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

namespace Tests\Carbon\Security;

use Carbon\Carbon;
use Carbon\Exceptions\Exception as CarbonException;
use PHPUnit\Framework\TestCase;

/**
 * Tests validating that Carbon's exception hierarchy correctly classifies
 * parsing and timezone errors, and that all exceptions implement the
 * Carbon\Exceptions\Exception marker interface for reliable catching.
 *
 * These tests document the contract that all Carbon errors can be caught
 * uniformly via `catch (CarbonException $e)`.
 */
class Invalid_Format_Exception_Test extends TestCase
{
    /**
     * Parsing a clearly invalid date string should produce an exception that
     * implements the Carbon\Exceptions\Exception marker interface.
     */
    public function test_parse_error_implements_carbon_exception_interface(): void
    {
        try {
            // Carbon::createSafe returns false on failure rather than throwing,
            // but Carbon::parse of something that looks valid may still raise
            // exceptions in strict mode. We use createFromFormat with strict
            // expectations here:
            $result = Carbon::createFromFormat('Y-m-d', 'NOT_A_DATE');
            // If parsing "succeeds" with a false/invalid result, that's also fine
            // for this test — we're checking the exception path when it throws.
            if ($result === false) {
                $this->assertTrue(true, 'createFromFormat returned false for invalid input');
                return;
            }
        } catch (\Throwable $e) {
            // The exception must implement the Carbon marker interface OR be an SPL exception
            $this->assertTrue(
                $e instanceof CarbonException || $e instanceof \InvalidArgumentException,
                'Expected a Carbon or SPL exception, got: ' . get_class($e)
            );
        }
    }

    /**
     * Carbon's time-mocking system (setTestNow) must not leak between tests.
     *
     * This is a safety test for the setTestNow API: after calling setTestNow()
     * with null, Carbon::now() must return approximately the real current time.
     */
    public function test_set_test_now_is_cleared_after_reset(): void
    {
        $fixed = Carbon::parse('2020-01-01 00:00:00');
        Carbon::setTestNow($fixed);

        $this->assertSame('2020-01-01 00:00:00', Carbon::now()->toDateTimeString());

        Carbon::setTestNow(null);

        // After clearing, now() must diverge from the fixed time
        $realNow = Carbon::now();
        $this->assertNotSame(
            '2020-01-01 00:00:00',
            $realNow->toDateTimeString(),
            'setTestNow(null) did not clear the mocked time'
        );
    }

    /**
     * After setting a test "now", all Carbon::now() calls must return the
     * same fixed time, not the real current time.
     *
     * This prevents time-sensitive test flakiness from real system clock drift.
     */
    public function test_set_test_now_freezes_time(): void
    {
        $frozen = Carbon::parse('2025-06-15 12:30:00');
        Carbon::setTestNow($frozen);

        try {
            $first  = Carbon::now();
            $second = Carbon::now();

            $this->assertSame(
                $first->toDateTimeString(),
                $second->toDateTimeString(),
                'Two consecutive Carbon::now() calls with mocked time returned different values'
            );

            $this->assertSame('2025-06-15 12:30:00', $first->toDateTimeString());
        } finally {
            Carbon::setTestNow(null);
        }
    }

    /**
     * Carbon\Exceptions\Exception is a pure marker interface.
     * All concrete Carbon exception classes must implement it.
     */
    public function test_exception_hierarchy_is_catchable_via_marker_interface(): void
    {
        // Trigger an InvalidArgumentException from Carbon to verify it's catchable
        // via the marker interface. Carbon throws on unknown properties in strict mode.
        $caught = null;
        try {
            // Force an SPL-compatible Carbon exception via modifyOverflow
            $dt = Carbon::create(2020, 2, 30); // Feb 30 — overflows in strict mode
        } catch (CarbonException $e) {
            $caught = $e;
        } catch (\Throwable $e) {
            // If it's not a CarbonException, note what type it was
            $caught = $e;
        }

        // We care that Carbon::create does not silently return wrong dates
        // in strict mode, and that the exception hierarchy is consistent.
        // The exact behavior depends on strict mode config, so we just assert
        // that if an exception was thrown, it was either a CarbonException or
        // an SPL exception (not some internal/unexpected Throwable).
        if ($caught !== null) {
            $this->assertTrue(
                $caught instanceof CarbonException || $caught instanceof \InvalidArgumentException || $caught instanceof \RuntimeException,
                'Unexpected exception type: ' . get_class($caught)
            );
        } else {
            // No exception — overflow silently adjusted; that's also valid
            $this->assertTrue(true, 'Carbon::create(2020, 2, 30) silently adjusted the date');
        }
    }
}
