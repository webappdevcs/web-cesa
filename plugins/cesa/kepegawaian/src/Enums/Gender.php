<?php

namespace Cesa\Kepegawaian\Enums;

enum Gender: string
{
    case Male = 'male';

    case Female = 'female';

    case Other = 'other';

    public static function options(): array
    {
        return [
            self::Male->value   => __('kepegawaian::enums/gender.male'),
            self::Female->value => __('kepegawaian::enums/gender.female'),
            self::Other->value  => __('kepegawaian::enums/gender.other'),
        ];
    }
}
