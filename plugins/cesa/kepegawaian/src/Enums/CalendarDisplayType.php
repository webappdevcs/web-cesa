<?php

namespace Cesa\Kepegawaian\Enums;

enum CalendarDisplayType: string
{
    case Working = 'working';

    case Off = 'off';

    case Holiday = 'holiday';

    public static function options(): array
    {
        return [
            self::Working->value => __('kepegawaian::enums/calendar-display-type.working'),
            self::Off->value     => __('kepegawaian::enums/calendar-display-type.off'),
            self::Holiday->value => __('kepegawaian::enums/calendar-display-type.holiday'),
        ];
    }
}
