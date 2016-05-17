<?php

namespace App\Modules\Admin\Presenters;

use App\Modules\Core\Model\EventModel;
use Nette\Utils\DateTime;


class DashboardPresenter extends BasePresenter
{
	/** @var EventModel @inject */
	public $eventModel;


	public function actionDefault()
	{
		$this->template->eventsCounts = $this->eventModel->getAll()
			->select('COUNT(*) AS all')
			->select('COUNT(IF(MONTH(start) = MONTH(?), 1, NULL)) AS thisMonth', $datetime = new DateTime)
			->select('COUNT(IF(MONTH(start) = MONTH(?), 1, NULL)) AS nextMonth', $datetime->modifyClone('+1 MONTH'))
			->where('start >= ?', $datetime)
			->where('state = ?', EventModel::STATE_APPROVED)
			->fetch();
	}
}
