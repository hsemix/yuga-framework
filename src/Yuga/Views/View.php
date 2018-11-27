<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Views;

use App;

class View
{
	protected $viewFile;
	protected $viewEngine;

	/**
	 * Get the yuga-view-engine instance
	 * 
	 * @param string $view 
	 * @param array|null $data
	 */
	public function __construct($view = null, array $data = null)
	{
		$this->viewEngine = App::make('view');
		$view = $this->processViewPath($view);

		$this->viewFile = $view;

		if ($data) {
			$this->with($data);
		}
	}

	protected function processViewPath($path = null)
	{
		if ($path)
			return str_replace(".", "/", $path);
	}

	/**
	 * Render the view to the user
	 * 
	 * @param null
	 * 
	 * @return string
	 */
	public function __toString()
	{
		return $this->viewEngine->display($this->viewFile);
	}

	/**
	 * Pass data to the view and bind it to variables
	 * 
	 * @param array|[] $data
	 * 
	 * @return static
	 */
	public function with($data = null, $value = null)
	{
		if (is_array($data)) {
			foreach ($data as $var => $value) {
				$this->viewEngine->$var = $value;	
			}
		} else {
			$this->viewEngine->$data = $value;
		}
		return $this;
	}

	/**
	 * Get the first view that exists in the array and render that instead
	 * 
	 * @param array|null
	 * 
	 * @return static
	 */
	public function first(array $views = null)
	{
		if ($views) {
			foreach ($views as $view) {
				if (file_exists(path('resources/views/' . $this->processViewPath($view). '.hax.php'))) {
					$this->viewFile = $view;
					break;
				} elseif (file_exists(path('resources/views/' . $this->processViewPath($view). '.php'))) {
					$this->viewFile = $view;
					break;
				}
			}
		}
		return $this;
	}
}