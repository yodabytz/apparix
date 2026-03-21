<?php

namespace App\Core;

class HolidayEffects
{
    // Master switch — set true to enable date-based holiday detection
    // Theme settings control whether effects actually display on the frontend
    private static bool $enabled = true;

    private static array $holidays = [
        'christmas' => [
            'month' => 12,
            'day' => 25,
            'name' => 'Christmas',
            'class' => 'holiday-christmas'
        ],
        'valentines' => [
            'month' => 2,
            'day' => 14,
            'name' => "Valentine's Day",
            'class' => 'holiday-valentines'
        ],
        'stpatricks' => [
            'month' => 3,
            'day' => 17,
            'name' => "St. Patrick's Day",
            'class' => 'holiday-stpatricks'
        ],
        'halloween' => [
            'month' => 10,
            'day' => 31,
            'name' => 'Halloween',
            'class' => 'holiday-halloween'
        ],
        'easter' => [
            'month' => 0, // Calculated dynamically
            'day' => 0,
            'name' => 'Easter',
            'class' => 'holiday-easter'
        ],
        'independence' => [
            'month' => 7,
            'day' => 4,
            'name' => 'Independence Day',
            'class' => 'holiday-independence'
        ],
        'newyear' => [
            'month' => 1,
            'day' => 1,
            'name' => "New Year's Day",
            'class' => 'holiday-newyear'
        ]
    ];

    /**
     * Get the currently active holiday (if any)
     * Returns null if no holiday is active
     * Supports ?holiday_preview=christmas (or other holiday key) for testing
     * Supports optional $previewHoliday param from theme settings
     */
    public static function getActiveHoliday(?string $previewHoliday = null): ?array
    {
        // Check for preview mode (URL param or theme setting) — always works
        $preview = $previewHoliday ?: ($_GET['holiday_preview'] ?? null);
        if ($preview && isset(self::$holidays[$preview])) {
            $holiday = self::$holidays[$preview];
            return [
                'key' => $preview,
                'name' => $holiday['name'],
                'class' => $holiday['class'],
                'date' => new \DateTime(),
                'daysUntil' => 0
            ];
        }

        // Check master switch
        if (!self::$enabled) {
            return null;
        }

        $now = new \DateTime();
        $currentYear = (int)$now->format('Y');

        foreach (self::$holidays as $key => $holiday) {
            // Handle Easter specially (movable date)
            if ($key === 'easter') {
                $easterDate = self::getEasterDate($currentYear);
                $holiday['month'] = (int)$easterDate->format('n');
                $holiday['day'] = (int)$easterDate->format('j');
            }

            // Create holiday date for current year
            $holidayDate = new \DateTime();
            $holidayDate->setDate($currentYear, $holiday['month'], $holiday['day']);
            $holidayDate->setTime(0, 0, 0);

            // Start date: 12 days before
            $startDate = clone $holidayDate;
            $startDate->modify('-12 days');

            // End date: midnight after the holiday (start of the next day)
            $endDate = clone $holidayDate;
            $endDate->modify('+1 day');
            $endDate->setTime(0, 0, 0);

            // Check if we're in the holiday window
            if ($now >= $startDate && $now < $endDate) {
                return [
                    'key' => $key,
                    'name' => $holiday['name'],
                    'class' => $holiday['class'],
                    'date' => $holidayDate,
                    'daysUntil' => (int)$now->diff($holidayDate)->format('%r%a')
                ];
            }

            // Also check previous year for New Year's (Dec dates leading to Jan 1)
            if ($key === 'newyear') {
                $holidayDate->setDate($currentYear + 1, 1, 1);
                $startDate = clone $holidayDate;
                $startDate->modify('-12 days');
                $endDate = clone $holidayDate;
                $endDate->modify('+1 day');

                if ($now >= $startDate && $now < $endDate) {
                    return [
                        'key' => $key,
                        'name' => $holiday['name'],
                        'class' => $holiday['class'],
                        'date' => $holidayDate,
                        'daysUntil' => (int)$now->diff($holidayDate)->format('%r%a')
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Calculate Easter date for a given year
     */
    private static function getEasterDate(int $year): \DateTime
    {
        if (function_exists('easter_days')) {
            $base = new \DateTime("$year-03-21");
            $days = easter_days($year);
            return $base->modify("+{$days} days");
        }

        // Fallback: Anonymous Gregorian algorithm (no calendar extension needed)
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTime("$year-$month-$day");
    }

    /**
     * Get the CSS class for the body element
     */
    public static function getBodyClass(): string
    {
        $holiday = self::getActiveHoliday();
        return $holiday ? $holiday['class'] : '';
    }

    /**
     * Check if a specific holiday is active
     */
    public static function isHolidayActive(string $key): bool
    {
        $holiday = self::getActiveHoliday();
        return $holiday && $holiday['key'] === $key;
    }
}
