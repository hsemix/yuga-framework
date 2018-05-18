<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Views;

use App;

class View
{
	private $ext = 'php';
	private $data = [];
	protected $viewEngine;
	protected $viewFile;
	public function __construct($view, $extras = null, $ext = null)
	{
		$starter = App::make('view');
		if ($ext) {
			$this->ext = $ext;
		}
		$view = str_replace(".", "/", $view);
		$file = $view;

		if ($extras) {
			$this->data = $extras;
		}
		
		foreach ($this->data as $var => $value) {
			$starter->$var = $value;	
		}
		
		return $starter->display($file);
	}

	// public function __construct($view, $extras = null)
	// {
	// 	$this->viewFile = $view;
	// 	$this->viewEngine = App::make('view');
	// 	if ($extras) {
	// 		$this->data = $extras;
	// 	}
	// }

	// public function with(array $data = null)
	// {
	// 	$view = clone $this;
	// 	$view->data = $data;
	// 	return $view;
	// }

	// public function __toString()
	// {
	// 	return $this->viewEngine->display($this->viewFile, $this->data);
	// }
}
