<?php

namespace Cesa\Kepegawaian\Enums;

enum DayOfWeek: string
{
    case Monday = 'monday';

    case Tuesday = 'tuesday';

    case Wednesday = 'wednesday';

    case Thursday = 'thursday';

    case Friday = 'friday';

    case Saturday = 'saturday';

    case Sunday = 'sunday';

    public static function options(): array
    {
        return [
            self::Monday->value     => __('kepegawaian::enums/day-of-week.monday'),
            self::Tuesday->value    => __('kepegawaian::enums/day-of-week.tuesday'),
            self::Wednesday->value  => __('kepegawaian::enums/day-of-week.wednesday'),
            self::Thursday->value   => __('kepegawaian::enums/day-of-week.thursday'),
            self::Friday->value     => __('kepegawaian::enums/day-of-week.friday'),
            self::Saturday->value   => __('kepegawaian::enums/day-of-week.saturday'),
            self::Sunday->value     => __('kepegawaian::enums/day-of-week.sunday'),
        ];
    }
}
