<?php

namespace Botble\JobBoard\Enums;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Supports\Enum;
use Illuminate\Support\HtmlString;

/**
 * @method static JobApplicationStatusEnum PENDING()
 * @method static JobApplicationStatusEnum CHECKED()
 * @method static JobApplicationStatusEnum SHORTLISTED()
 * @method static JobApplicationStatusEnum INTERVIEWED()
 * @method static JobApplicationStatusEnum OFFERED()
 */
class JobApplicationStatusEnum extends Enum
{
    public const PENDING     = 'pending';
    public const CHECKED     = 'checked';
    public const SHORTLISTED = 'shortlisted';
    public const INTERVIEWED = 'interviewed';
    public const OFFERED     = 'offered';

    public static $langPath = 'plugins/job-board::job-application.statuses';

    public function toHtml(): HtmlString|string
    {
        $color = match ($this->value) {
            self::PENDING     => 'warning',
            self::CHECKED     => 'info',
            self::SHORTLISTED => 'primary',
            self::INTERVIEWED => 'purple',
            self::OFFERED     => 'success',
            default           => 'secondary',
        };

        return BaseHelper::renderBadge($this->label(), $color);
    }
}
