<?php

namespace App\Support;

use Carbon\CarbonImmutable;

final class FinanceDateRange
{
    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    public static function utcBounds(string $from, string $to): array
    {
        $displayTimezone = config('app.display_timezone', 'Asia/Ho_Chi_Minh');

        return [
            CarbonImmutable::parse($from, $displayTimezone)->startOfDay()->setTimezone('UTC'),
            CarbonImmutable::parse($to, $displayTimezone)->addDay()->startOfDay()->setTimezone('UTC'),
        ];
    }
}
