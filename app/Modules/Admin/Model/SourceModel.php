<?php declare(strict_types=1);

namespace App\Modules\Admin\Model;

use App\Modules\Core\Model\BaseModel;


final class SourceModel extends BaseModel
{
	/**
	 * @var string
	 */
	const TABLE_NAME = 'sources';

	/**
	 * @var int[]
	 */
	const FREQUENCY_TYPES = [
		'daily' => 1,
		'twiceAWeek' => 3,
		'weekly' => 7,
		'fortnightly' => 14,
		'monthly' => 30,
		'quarterly' => 90,
		'half-yearly' => 183,
		'yearly' => 365,
	];
}