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
		
		$starter->display($file);
	}
}