<?php

namespace Yuga\Database\Tracy;

use Tracy\IBarPanel;
use Yuga\Database\Query\DB;

class DatabasePanel implements IBarPanel
{
    /**
	 * Renders tab.
	 */
	public function getTab(): string
	{
		return '<span title="Database Queries">
			<svg>....</svg>
			<span class="tracy-label">Elegant Database</span>
		</span>';
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		$lastQuery = 'No Query was Executed';

		if (DB::getLastQuery() != null) {
			$lastQuery = DB::getLastQuery()->getRawSql();
		}
		return '<h1>Last Executed Query</h1>

		<div class="tracy-inner">
		<div class="tracy-inner-container">
		<div class="tracy-InfoPan">' . $lastQuery . '</div>
		</div>
		</div>';
	}
}