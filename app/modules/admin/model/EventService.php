<?php

namespace App\Modules\Admin\Model;


use App\Modules\Core\Model\Entity\Event;
use App\Modules\Core\Model\EventModel;
use App\Modules\Core\Model\EventSources\Facebook\FacebookEventSource;
use App\Modules\Core\Model\EventTagModel;
use App\Modules\Core\Model\TagModel;
use Nette\Utils\DateTime;

class EventService
{
	/** @var EventModel */
	private $eventModel;

	/** @var TagModel */
	private $tagModel;

	/** @var EventTagModel */
	private $eventTagModel;

	/** @var FacebookEventSource */
	private $facebookEventSource;

	const PLATFORM_FACEBOOK = 'facebook';


	public function __construct(EventModel $eventModel,
	                            TagModel $tagModel,
	                            EventTagModel $eventTagModel,
								FacebookEventSource $facebookEventSource)
	{
		$this->eventModel = $eventModel;
		$this->tagModel = $tagModel;
		$this->eventTagModel = $eventTagModel;
		$this->facebookEventSource = $facebookEventSource;
	}


	public function createEvent($values)
	{
		// Create event
		$event = $this->eventModel->insert([
			'name' => $values->name,
			'description' => $values->description ?: null,
			'origin_url' => $values->origin_url ?: null,
			'start' => DateTime::createFromFormat(\App\Modules\Core\Utils\DateTime::DATETIME_FORMAT, $values->start),
			'end' => $values->end
				? DateTime::createFromFormat(\App\Modules\Core\Utils\DateTime::DATETIME_FORMAT, $values->end)
				: null,
			'image' => $values->image ?: null,
			'rate' => $values->rate,
		]);

		$this->addTags($values->tags, $event->id);
	}


	public function updateEvent($values)
	{
		// Create event
		$this->eventModel->getAll()->wherePrimary($values->id)->update([
			'name' => $values->name,
			'description' => $values->description ?: null,
			'origin_url' => $values->origin_url ?: null,
			'start' => DateTime::createFromFormat(\App\Modules\Core\Utils\DateTime::DATETIME_FORMAT, $values->start),
			'end' => $values->end
				? DateTime::createFromFormat(\App\Modules\Core\Utils\DateTime::DATETIME_FORMAT, $values->end)
				: null,
			'image' => $values->image ?: null,
			'rate' => $values->rate,
		]);

		//TODO remove missing tags, add new ones
		// Remove previous tags
		$this->eventTagModel->getAll()
			->where(['event_id' => $values->id])
			->delete();

		$this->addTags($values->tags, $values->id);
	}


	/**
	 * Add tags for event
	 * @param $tags
	 * @param $eventId
	 */
	private function addTags($tags, $eventId)
	{
		foreach ($tags as $tagValues) {
			if (!$tagValues->code) {
				continue;
			}

			$tag = $this->tagModel->getAll()->where(['code' => $tagValues->code])->fetch();
			$this->eventTagModel->insert([
				'event_id' => $eventId,
				'tag_id' => $tag->id,
				'rate' => $tagValues->rate,
			]);
		}
	}

	/**
	 * Get event by platform specific ID
	 * 
	 * @param $id
	 * @param $platform
	 * @return Event
	 */
	public function getEventFromPlatform($id, $platform) : Event
	{
		if ($platform === self::PLATFORM_FACEBOOK) {
			return $this->facebookEventSource->getEventById($id);
		}
		return null;
	}
}
