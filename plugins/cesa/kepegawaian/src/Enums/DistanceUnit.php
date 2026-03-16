<?php

namespace Cesa\Kepegawaian\Enums;

enum DistanceUnit: string
{
    case KILOMETER = 'kilometer';

    case METER = 'meter';

    public static function options(): array
    {
        return [
            self::KILOMETER->value => __('kepegawaian::enums/distance-unit.kilometer'),
            self::METER->value     => __('kepegawaian::enums/distance-unit.meter'),
        ];
    }
}
