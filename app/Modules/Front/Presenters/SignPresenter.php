<?php declare(strict_types=1);

namespace App\Modules\Front\Presenters;

use Nette\Application\UI\Presenter;

final class SignPresenter extends Presenter
{
	/**
	 * @var \Kdyby\Translation\Translator @inject
	 */
	public $translator;

	/**
	 * @var string[]
	 */
	const BYE = [
		'Adiós',
		'Aloha',
		'Arrivederci',
		'Ciao',
		'Auf Wiedersehen',
		'Au revoir',
		'Bon voyage',
		'Sayonara',
		'Měj se',
	];


	public function actionOut()
	{
		if (!$this->getUser()->isLoggedIn()) {
			$this->redirect('Homepage:');
		}

		$this->getUser()->logout(true);

		$this->flashMessage($this->translator->translate('front.sign.out', [
			'bye' => self::BYE[array_rand(self::BYE)],
		]));
		$this->redirect('Homepage:');
	}
}
