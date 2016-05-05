<?php

namespace App\Modules\Core\Presenters;

use Nette;
use Nette\Utils\DateTime;


/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	/** @var \Kdyby\Translation\Translator @inject */
	public $translator;


	protected function createTemplate()
	{
		$template = parent::createTemplate();

		$template->addFilter('datetime', function (DateTime $a, DateTime $b = null) {
			$result = $a->format('j. n. ');
			if ($b && $a != $b) {
				$result .= '&nbsp;&ndash;&nbsp;' . $b->format('j. n. ');
			}
			return $result . $b->format('Y');
		});

		$template->addFilter('username', function (Nette\Security\Identity $identity) {
			return $identity->fullname ?: $identity->email ?: $this->translator->translate('front.nav.user');
		});

		return $template;
	}

	protected function beforeRender()
	{
		parent::beforeRender();

		$this->template->parameters = $this->context->getParameters();
	}
}
