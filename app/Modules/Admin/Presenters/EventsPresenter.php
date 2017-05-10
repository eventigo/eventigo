<?php declare(strict_types=1);

namespace App\Modules\Admin\Presenters;

use App\Modules\Admin\Components\EventForm\EventFormFactoryInterface;
use App\Modules\Admin\Components\EventsTable\NotApprovedEventsTableFactoryInterface;
use App\Modules\Admin\Model\SourceService;
use App\Modules\Core\Model\EventModel;
use App\Modules\Core\Utils\DateTime as EventigoDateTime;
use Nette\Application\Request;
use Nette\Utils\DateTime;


final class EventsPresenter extends AbstractBasePresenter
{
	/**
	 * @var EventFormFactoryInterface @inject
	 */
	public $eventFormFactory;

	/**
	 * @var EventModel @inject
	 */
	public $eventModel;

	/**
	 * @var SourceService @inject
	 */
	public $sourceService;

	/**
	 * @var NotApprovedEventsTableFactoryInterface @inject
	 */
	public $notApprovedEventsTableFactory;


	public function actionUpdate($id)
	{
		$event = $this->eventModel->getAll()->wherePrimary($id)->fetch();

		$defaults = $event->toArray();
		$defaults['start'] = DateTime::from($defaults['start'])->format(EventigoDateTime::DATETIME_FORMAT);
		$defaults['end'] = $defaults['end']
			? DateTime::from($defaults['end'])->format(EventigoDateTime::DATETIME_FORMAT)
			: null;
		$defaults['tags'] = [];
		foreach ($event->related('events_tags') as $eventTag) {
			$defaults['tags'][] = [
				'code' => $eventTag->tag->code,
				'rate' => $eventTag->rate,
			];
		}

		if ($this->getRequest()->getMethod() === Request::FORWARD) {
			$defaults['state'] = EventModel::STATE_APPROVED;
		}

		// Set image from previous event in series if none
		if (!$defaults['image'] && $event['event_series_id']) {
			$previousEvent = $this->eventModel->findPreviousEvent($event['event_series_id']);
			if ($previousEvent) {
				$defaults['image'] = $previousEvent->image;
			}
		}

		$this['eventForm-form']->setDefaults($defaults);
	}


	public function renderUpdate()
	{
		$this->template->setFile(__DIR__ . '/templates/Events/create.latte');
	}


	protected function createComponentEventForm()
	{
		$control = $this->eventFormFactory->create();

		$control->onCreate[] = function () {
			$this->flashMessage($this->translator->translate('admin.eventForm.success'), 'success');
			$this->redirect('Events:default');
		};

		$control->onUpdate[] = function () {
			$this->flashMessage($this->translator->translate('admin.eventForm.success'), 'success');
			$this->redirect('Events:default');
		};

		return $control;
	}


	public function handleCrawlSources()
	{
		$addedEvents = $this->sourceService->crawlSources();

		if ($addedEvents > 0) {
			$this->flashMessage($this->translator->translate('admin.events.crawlSources.success',
				$addedEvents, ['events' => $addedEvents]), 'success');
		} else {
			$this->flashMessage($this->translator->translate('admin.events.crawlSources.noEvents'));
		}

		$this->redirect('this');
	}


	public function actionApprove($id)
	{
		$this->forward('update', $id);
	}


	protected function createComponentNotApprovedEventsTable()
	{
		return $this->notApprovedEventsTableFactory->create(
			$this->eventModel->getAll()
				->where('state', EventModel::STATE_NOT_APPROVED)
				->where('start > ?', new DateTime)
		);
	}
}
