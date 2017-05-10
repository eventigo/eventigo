<?php declare(strict_types=1);

namespace App\Modules\Admin\Model;

use App\Modules\Core\Model\Entity\Event;
use App\Modules\Core\Model\EventModel;
use App\Modules\Core\Model\EventSources\Facebook\FacebookEventSource;
use App\Modules\Core\Model\EventTagModel;
use App\Modules\Core\Model\TagModel;
use App\Modules\Core\Utils\DateTime;
use InvalidArgumentException;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime as NetteDateTime;

final class EventService
{
	/**
	 * @var string
	 */
	public const PLATFORM_FACEBOOK = 'facebook';

	/**
	 * @var EventModel
	 */
	private $eventModel;

	/**
	 * @var TagModel
	 */
	private $tagModel;

	/**
	 * @var EventTagModel
	 */
	private $eventTagModel;

	/**
	 * @var FacebookEventSource
	 */
	private $facebookEventSource;

	public function __construct(
		EventModel $eventModel,
		TagModel $tagModel,
		EventTagModel $eventTagModel,
		FacebookEventSource $facebookEventSource
) {
		$this->eventModel = $eventModel;
		$this->tagModel = $tagModel;
		$this->eventTagModel = $eventTagModel;
		$this->facebookEventSource = $facebookEventSource;
	}

	public function createEvent(ArrayHash $values): void
	{
		// Create event
		$event = $this->eventModel->insert([
			'name' => $values->name,
			'description' => $values->description ?: null,
			'origin_url' => $values->origin_url ?: null,
			'start' => NetteDateTime::createFromFormat(DateTime::DATETIME_FORMAT, $values->start),
			'end' => $values->end
				? NetteDateTime::createFromFormat(DateTime::DATETIME_FORMAT, $values->end)
				: null,
			'image' => $values->image ?: null,
			'rate' => $values->rate,
			'state' => $values->state,
			'approved' => $values->state === EventModel::STATE_APPROVED ? new DateTime : null,
			'event_series_id' => $values->event_series_id,
		]);

		$this->addTags($values->tags, (int) $event->id);
	}

	public function updateEvent(ArrayHash $values): void
	{
		$event = $this->eventModel->getAll()->wherePrimary($values->id)->fetch();

		// Create event
		$this->eventModel->getAll()->wherePrimary($values->id)->update([
			'name' => $values->name,
			'description' => $values->description ?: null,
			'origin_url' => $values->origin_url ?: null,
			'start' => NetteDateTime::createFromFormat(DateTime::DATETIME_FORMAT, $values->start),
			'end' => $values->end
				? NetteDateTime::createFromFormat(DateTime::DATETIME_FORMAT, $values->end)
				: null,
			'image' => $values->image ?: null,
			'rate' => $values->rate,
			'state' => $values->state,
			'approved' => $values->state === EventModel::STATE_APPROVED && ! $event->approved
				? new DateTime : $event->approved,
			'event_series_id' => $values->event_series_id,
		]);

		//TODO remove missing tags, add new ones
		// Remove previous tags
		$this->eventTagModel->getAll()
			->where(['event_id' => $values->id])
			->delete();

		$this->addTags($values->tags, (int) $values->id);
	}

	/**
	 * Get event by platform specific ID.
	 *
	 * @throws \Kdyby\Facebook\FacebookApiException
	 * @throws InvalidArgumentException
	 */
	public function getEventFromPlatform(string $id, string $platform): Event
	{
		if ($platform === self::PLATFORM_FACEBOOK) {
			return $this->facebookEventSource->getEventById($id);
		}
		throw new InvalidArgumentException('Invalid platform');
	}

	private function addTags(ArrayHash $tags, int $eventId): void
	{
		foreach ($tags as $tagValues) {
			if (! $tagValues->code) {
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
}
