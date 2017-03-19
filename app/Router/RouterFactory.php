<?php declare(strict_types=1);

namespace App\Router;

use Nette\Application\IRouter;
use Nette\Application\Routers\RouteList;
use Nette\Application\Routers\Route;


class RouterFactory
{

	public static function createRouter(): IRouter
	{
		$router = new RouteList;

		// Admin
		$adminRouter = new RouteList('Admin');
		$adminRouter[] = new Route('admin/<presenter>/<action>[/<id>]', 'Dashboard:default');
		$router[] = $adminRouter;

		// Nesletter
		$newsletterRouter = new RouteList('Newsletter');
		$newsletterRouter[] = new Route('newsletter/<hash [a-z0-9]{32}>', 'Newsletter:default');
		$newsletterRouter[] = new Route('newsletter/dynamic/<userId>', 'Newsletter:dynamic');
		$newsletterRouter[] = new Route('newsletter/<action>/<hash>', 'Newsletter:default');
		$router[] = $newsletterRouter;

		// Email
		$emailRouter = new RouteList('Email');
		$emailRouter[] = new Route('email/login', 'Email:login');
		$router[] = $emailRouter;

		// Front
		$router[] = new Route('profile/settings/<token>', 'Front:Profile:settings');
		$router[] = new Route('discover/?', 'Front:Homepage:discover');
		$router[] = new Route('<presenter>/<action>[/<id>]', [
			'module' => 'Front',
			'presenter' => 'Homepage',
			'action' => 'default'
		]);
		return $router;
	}

}
