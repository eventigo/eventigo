<?php declare(strict_types=1);

namespace App\Modules\Admin\Model;

use App\Modules\Core\Model\AbstractBaseModel;

final class SourceModel extends AbstractBaseModel
{
    /**
     * @var int[]
     */
    public const FREQUENCY_TYPES = [
        'daily' => 1,
        'twiceAWeek' => 3,
        'weekly' => 7,
        'fortnightly' => 14,
        'monthly' => 30,
        'quarterly' => 90,
        'half-yearly' => 183,
        'yearly' => 365,
    ];

    /**
     * @var string
     */
    protected const TABLE_NAME = 'sources';
}
