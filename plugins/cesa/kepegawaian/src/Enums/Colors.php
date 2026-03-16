<?php

namespace Cesa\Kepegawaian\Enums;

enum Colors: string
{
    case Danger = 'danger';

    case Gray = 'gray';

    case Info = 'info';

    case Success = 'success';

    case Warning = 'warning';

    public static function options(): array
    {
        return [
            self::Danger->value  => __('kepegawaian::enums/colors.danger'),
            self::Gray->value    => __('kepegawaian::enums/colors.gray'),
            self::Info->value    => __('kepegawaian::enums/colors.info'),
            self::Success->value => __('kepegawaian::enums/colors.success'),
            self::Warning->value => __('kepegawaian::enums/colors.warning'),
        ];
    }
}
