<?php declare(strict_types=1);

namespace App\Modules\Admin\Components\EventForm;

use App\Modules\Admin\Model\EventService;
use App\Modules\Admin\Model\OrganiserService;
use App\Modules\Core\Components\BaseControl;
use App\Modules\Core\Components\Form\Form;
use App\Modules\Core\Model\EventModel;
use App\Modules\Core\Model\TagModel;
use Kdyby\Facebook\FacebookApiException;
use Kdyby\Translation\Translator;
use Nette\Database\UniqueConstraintViolationException;


class EventForm extends BaseControl
{
	/** @var TagModel */
	private $tagModel;

	/** @var EventService */
	private $eventService;

	/** @var array */
	public $onCreate = [];

	/** @var array */
	public $onUpdate = [];

	/** @var OrganiserService */
	private $organiserService;


	public function __construct(Translator $translator,
	                            TagModel $tagModel,
	                            EventService $eventService,
	                            OrganiserService $organiserService)
	{
		parent::__construct($translator);
		$this->tagModel = $tagModel;
		$this->eventService = $eventService;
		$this->organiserService = $organiserService;
	}


	public function render()
	{
		$this['form']->render();
	}


	protected function createComponentForm(): Form
	{
		$form = new Form;
		$form->setTranslator($this->translator->domain('admin.eventForm'));

		// Event
		$form->addGroup();
		$series = $this->organiserService->getOrganisersSeries();
		$form->addSelect('event_series_id', $this->translator->translate('admin.eventForm.eventSeries'))
			->setItems(OrganiserService::formatSeriesForSelect($series))
			->setPrompt($this->translator->translate('admin.eventForm.eventSeries.prompt'))
			->setTranslator(null);
		$form->addText('name', 'name')
			->setRequired('name.required')
			->setAttribute('autofocus', true);
		$form->addText('start', 'start')
			->setRequired('start.required')
			->setAttribute('class', 'datetime');
		$form->addText('end', 'end')
			->setAttribute('class', 'datetime');
		$form->addText('origin_url', 'originUrl')
			->addCondition(Form::FILLED)
			->addRule(Form::URL, 'originUrl.wrong');
		$form->addSubmit('facebook_load', 'loadFromFb')
			->setValidationScope([$form['origin_url']])
			->setAttribute('class', 'btn btn-primary');
		$form->addTextArea('description', 'description')
			->setRequired('description.required');
		$form->addText('image', 'image')
			->addCondition(Form::FILLED)
			->addRule(Form::URL, 'image.wrong')
			->addRule(Form::PATTERN, 'image.notSecure', '^https:.*');
		$form->addSelect('rate', $this->translator->translate('admin.eventForm.rate'), $this->getEventRates())
			->setPrompt($this->translator->translate('admin.eventForm.rate.prompt'))
			->setRequired('rate.required')
			->setTranslator(null);
		$form->addSelect('state', 'state', array_combine(EventModel::STATES, EventModel::STATES));

		// Tags
		$form->addGroup();
		$tags = $this->tagModel->getAll()->fetchPairs('code', 'name');
		$tagsContainer = $form->addContainer('tags');
		for ($i = 0; $i < 5; $i++) {
			$tagContainer = $tagsContainer->addContainer($i);

			$codeControl = $tagContainer->addSelect('code', $this->translator->translate('admin.eventForm.tag'), $tags)
				->setPrompt($this->translator->translate('admin.eventForm.tag.prompt'))
				->setTranslator(null);

			$rateControl = $tagContainer->addSelect(
					'rate',
					$this->translator->translate('admin.eventForm.tag.rate'),
					$this->getTagsRates()
				)
				->setPrompt($this->translator->translate('admin.eventForm.tag.rate.prompt'))
				->setTranslator(null);
			$rateControl->addConditionOn($codeControl, Form::FILLED)
				->setRequired($this->translator->translate('tag.rate.required'));
		}

		$form->addHidden('id');

		$form->addSubmit('save', 'save')
			->setAttribute('class', 'btn btn-success');
		$form->onSubmit[] = [$this, 'processForm'];

		return $form;
	}


	public function processForm(Form $form)
	{
		$values = $form->getValues();

		// Loading from Facebook
		if ($form['facebook_load']->isSubmittedBy()) {

			// Parse id from url
			preg_match( '/(?<=\/)\d{5,}/' , $values['origin_url'], $id);

			// Get event from fb
			try {
				$event = $this->eventService->getEventFromPlatform($id[0], EventService::PLATFORM_FACEBOOK);
			} catch (FacebookApiException $e) {
				$form->addError($e->getMessage());
				return;
			}

			// Set form values
			$values['name'] = $event->getName();
			$values['description'] = $event->getDescription();
			$values['start'] = $event->getStart() ? $event->getStart()->format('d. m. Y H:i') : null;
			$values['end'] = $event->getEnd() ? $event->getEnd()->format('d. m. Y H:i') : null;
			$values['image'] = $event->getImage();
			$values['rate'] = $event->getRate();
			$form->setValues($values, true);

			return;
		}

		if (!$values->id) {
			try {
				$this->eventService->createEvent($values);
				$this->onCreate();
			} catch (UniqueConstraintViolationException $e) {
				$form->addError($this->translator->trans('admin.eventForm.error.alreadyExists'));
				return;
			}
		} else {
			$this->eventService->updateEvent($values);
			$this->onUpdate();
		}
	}


	private function getEventRates(): array
	{
		return [
			1 => $this->translator->translate('admin.eventForm.rate.meetup'),
			$this->translator->translate('admin.eventForm.rate.bigMeetup'),
			$this->translator->translate('admin.eventForm.rate.conference'),
			$this->translator->translate('admin.eventForm.rate.bigConference'),
			$this->translator->translate('admin.eventForm.rate.extraConference'),
		];
	}


	private function getTagsRates(): array
	{
		return [
			1 => $this->translator->translate('admin.eventForm.tag.rate.little'),
			$this->translator->translate('admin.eventForm.tag.rate.more'),
			$this->translator->translate('admin.eventForm.tag.rate.good'),
			$this->translator->translate('admin.eventForm.tag.rate.reallyGood'),
			$this->translator->translate('admin.eventForm.tag.rate.total'),
		];
	}
}
