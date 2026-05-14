<?php

namespace Botble\JobBoard\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static SalaryTypeEnum NEGOTIABLE()
 * @method static SalaryTypeEnum COMPETITIVE()
 * @method static SalaryTypeEnum HIDDEN()
 * @method static SalaryTypeEnum FIXED()
 */
class SalaryTypeEnum extends Enum
{
    public const NEGOTIABLE = 'negotiable';

    public const COMPETITIVE = 'competitive';

    public const HIDDEN = 'hidden';

    public const FIXED = 'fixed';

    public static $langPath = 'plugins/job-board::job.salary_types';

    public function toHtml(): string|HtmlString
    {
        $color = match ($this->value) {
            self::NEGOTIABLE => 'info',
            self::COMPETITIVE => 'warning',
            self::HIDDEN => 'secondary',
            self::FIXED => 'success',
            default => 'primary',
        };

        return BaseHelper::renderBadge($this->label(), $color);
    }
}
