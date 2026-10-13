<?php

use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

it('returns morning for 05:00-11:59', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 05:00:00'));
    expect(time_greeting())->toBe('morning');

    Carbon::setTestNow(Carbon::parse('2026-10-13 11:59:00'));
    expect(time_greeting())->toBe('morning');
});

it('returns afternoon for 12:00-16:59', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 12:00:00'));
    expect(time_greeting())->toBe('afternoon');

    Carbon::setTestNow(Carbon::parse('2026-10-13 16:59:00'));
    expect(time_greeting())->toBe('afternoon');
});

it('returns evening for 17:00-20:59', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 17:00:00'));
    expect(time_greeting())->toBe('evening');

    Carbon::setTestNow(Carbon::parse('2026-10-13 20:59:00'));
    expect(time_greeting())->toBe('evening');
});

it('returns night for 21:00-04:59', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 21:00:00'));
    expect(time_greeting())->toBe('night');

    Carbon::setTestNow(Carbon::parse('2026-10-13 23:59:00'));
    expect(time_greeting())->toBe('night');

    Carbon::setTestNow(Carbon::parse('2026-10-14 04:59:00'));
    expect(time_greeting())->toBe('night');
});

it('respects a timezone override', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 10:00:00', 'UTC'));
    expect(time_greeting('Africa/Dar_es_Salaam'))->toBe('afternoon');
});

it('greeting_label falls back to English when no translation exists', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 06:00:00'));
    expect(greeting_label())->toBe('Good Morning');
});

it('greeting_label uses translation keys when present', function () {
    Carbon::setTestNow(Carbon::parse('2026-10-13 06:00:00'));
    app('translator')->setLocale('sw');
    expect(greeting_label())->toBe('Good asubuhi');
});

afterEach(function () {
    Carbon::setTestNow(null);
    app('translator')->setLocale('en');
});