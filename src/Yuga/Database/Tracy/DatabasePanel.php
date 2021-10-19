<?php

namespace Yuga\Database\Tracy;

use Tracy\IBarPanel;

class DatabasePanel implements IBarPanel
{
    /**
	 * Renders tab.
	 */
	public function getTab(): string
	{
		return '<span title="Explaining tooltip">
			<svg>....</svg>
			<span class="tracy-label">Elegant Database</span>
		</span>';
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		return '<h1>Title</h1>

		<div class="tracy-inner">
		<div class="tracy-inner-container">
			... content ...
		</div>
		</div>';
	}
}