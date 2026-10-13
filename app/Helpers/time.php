<?php

if (!function_exists('time_greeting')) {
    /**
     * Return a greeting appropriate to the current local time.
     *
     * Morning:  05:00 - 11:59
     * Afternoon: 12:00 - 16:59
     * Evening:  17:00 - 20:59
     * Night:    21:00 - 04:59
     *
     * @param string|null $timezone  Optional timezone override.
     * @return string  One of "morning", "afternoon", "evening", "night".
     */
    function time_greeting(?string $timezone = null): string
    {
        $now = $timezone
            ? Carbon\Carbon::now($timezone)
            : Carbon\Carbon::now();

        $hour = (int) $now->format('H');

        return match (true) {
            $hour >= 5 && $hour < 12   => 'morning',
            $hour >= 12 && $hour < 17  => 'afternoon',
            $hour >= 17 && $hour < 21  => 'evening',
            default                    => 'night',
        };
    }
}

if (!function_exists('greeting_label')) {
    /**
     * Localised greeting label ("Good morning", "Good evening", ...).
     *
     * Falls back to English when no translation key exists for the locale.
     *
     * @param string|null $timezone  Optional timezone override.
     * @return string
     */
    function greeting_label(?string $timezone = null): string
    {
        $greeting = time_greeting($timezone);
        $key = "time.{$greeting}";

        try {
            $translator = app('translator');
        } catch (\Throwable) {
            return 'Good ' . ucfirst($greeting);
        }

        $translated = $translator->get($key);

        return $translated === $key
            ? 'Good ' . ucfirst($greeting)
            : $translated;
    }
}