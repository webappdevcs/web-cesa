<?php

namespace Cesa\Kepegawaian\Enums;

enum WeekType: string
{
    case All = 'all';

    case Even = 'even';

    case Odd = 'odd';

    public static function options(): array
    {
        return [
            self::All->value  => __('kepegawaian::enums/week-type.all'),
            self::Even->value => __('kepegawaian::enums/week-type.even'),
            self::Odd->value  => __('kepegawaian::enums/week-type.odd'),
        ];
    }
}
