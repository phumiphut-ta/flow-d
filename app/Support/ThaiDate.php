<?php

namespace App\Support;

use Carbon\Carbon;

class ThaiDate
{
    protected static array $thaiMonthsShort = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
    ];

    /**
     * Format to d/m/(Y+543)
     */
    public static function format(?Carbon $date): string
    {
        if (! $date) {
            return '-';
        }

        $buddhistYear = $date->year + 543;

        return $date->format("d/m/{$buddhistYear}");
    }

    /**
     * Format to d/m/(Y+543) H:i น.
     */
    public static function formatDateTime(?Carbon $date, bool $showSuffix = true): string
    {
        if (! $date) {
            return '-';
        }

        $buddhistYear = $date->year + 543;
        $suffix = $showSuffix ? ' น.' : '';

        return $date->format("d/m/{$buddhistYear} H:i").$suffix;
    }

    /**
     * Format to Thai readable: "29 ต.ค. 2569"
     */
    public static function formatThaiReadable(?Carbon $date): string
    {
        if (! $date) {
            return '-';
        }

        $day = $date->day;
        $month = self::$thaiMonthsShort[$date->month] ?? '';
        $buddhistYear = $date->year + 543;

        return "{$day} {$month} {$buddhistYear}";
    }
}
