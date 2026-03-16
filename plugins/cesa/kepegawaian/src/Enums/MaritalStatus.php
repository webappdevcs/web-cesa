<?php

namespace Cesa\Kepegawaian\Enums;

enum MaritalStatus: string
{
    case Single = 'single';

    case Married = 'married';

    case Divorced = 'divorced';

    case Widowed = 'widowed';

    public static function options(): array
    {
        return [
            self::Single->value   => __('kepegawaian::enums/marital-status.single'),
            self::Married->value  => __('kepegawaian::enums/marital-status.married'),
            self::Divorced->value => __('kepegawaian::enums/marital-status.divorced'),
            self::Widowed->value  => __('kepegawaian::enums/marital-status.widowed'),
        ];
    }
}
