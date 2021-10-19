<?php

namespace Yuga\Views\Tracy;

use Tracy\IBarPanel;

class HaxExtensionsPanel implements IBarPanel
{
    /**
	 * Renders tab.
	 */
	public function getTab(): string
	{
		return '<span title="Explaining tooltip">
			<svg>....</svg>
			<span class="tracy-label">Hax Extensions</span>
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