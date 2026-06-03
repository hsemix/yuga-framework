<?php
namespace Yuga\Views\Widgets\Debug;

use Yuga\Views\Widgets\Widget;

class WidgetDebug extends Widget
{
	protected $group;

	public function __construct(protected array $stack)
	{
		parent::__construct();

		$this->getSite()->addWrappedCss('css/yuga-debug.css', 'debug');
		$this->getSite()->addWrappedJs('js/yuga-debug.js', 'debug');

		$this->setTemplate(null);
	}

	protected function getTemplatePath()
	{
		$path = explode('\\', static::class);
		$path = array_slice($path, 2);

		return env('framework_path') . '/views/content/' . implode(DIRECTORY_SEPARATOR, $path) . '.php';
	}

	/**
	 * Render debug
	 * @return string
	 */
	public function render()
	{
		$this->renderContent();
		$this->renderTemplate();

		return $this->_contentHtml;
	}

}