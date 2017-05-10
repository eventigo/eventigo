<?php declare(strict_types=1);

namespace App\Modules\Admin\Components\SourcesTable;

use Nette\Database\Table\Selection;

interface SourcesTableFactoryInterface
{
	public function create(Selection $dataSource): SourcesTable;
}
