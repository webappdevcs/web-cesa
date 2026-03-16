<?php

namespace Cesa\Kepegawaian\Enums;

enum DayPeriod: string
{
    case Morning = 'morning';

    case Afternoon = 'afternoon';

    case Evening = 'evening';

    case Night = 'night';

    public static function options(): array
    {
        return [
            self::Morning->value   => __('kepegawaian::enums/day-period.morning'),
            self::Afternoon->value => __('kepegawaian::enums/day-period.afternoon'),
            self::Evening->value   => __('kepegawaian::enums/day-period.evening'),
            self::Night->value     => __('kepegawaian::enums/day-period.night'),
        ];
    }
}
