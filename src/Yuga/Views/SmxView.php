<?php
namespace Yuga\Views;

use Exception;
use Yuga\Http\Request;
use Yuga\Session\Session;
use Yuga\Validate\Message;
use Yuga\Views\Inheritance\View;

class SmxView extends View
{
    private $hax = '.hax.php';

    protected $compilers = [
        'opening',
        'echo',
        'closing',
        'else',
        'yield',
        'section',
        'endsection',
        'extends',
        'forelse',
        'endforelse',
        'include',
        'tags',
        'tags_opening',
        'tags_closing',
        'parent',
        'echo_escape'
    ];
    
    public function __construct($template_dir = NULL) 
    {
        if ($template_dir) {
            $this->template_dir = $template_dir;
        }
        $this->vars['session'] = \App::make('session');
        $this->vars['request'] = new Request;
        $this->vars['errors'] = new Message;
        if ($this->session->exists('errors')) {
            $this->vars['errors'] = $this->session->get('errors');
        }
    }
    
    
    public function displayOld($temp, $data = false)
    {
        $temp = str_replace('.', '/', $temp);
        if (file_exists($this->getTemplateDirectory().$temp.$this->hax)) {
           echo $this->renderHaxTemplate($temp, $data);
        } else {
            if ($data) {
                echo $this->renderView($temp, $data);
            } else {
                echo $this->renderView($temp);
            }
        }
    }

    public function display($temp, $data = false)
    {
        $temp = str_replace('.', '/', $temp);
        if (file_exists($this->getTemplateDirectory().$temp.$this->hax)) {
           return (string)$this->renderHaxTemplate($temp, $data);
        } else {
            if ($data) {
                return (string)$this->renderView($temp, $data);
            } else {
                return(string)$this->renderView($temp);
            }
        }
    }
    
    protected function createStorageDirectories()
    {
        if (!is_dir($directory = path('storage/hax'))) {
            mkdir($directory, 0777, true);
        }
    }
    
    public function renderHaxTemplate($templateName, array $data = null) 
    {
        $tempContents = $this->getTemplateDirectory().$templateName.$this->hax;
        $compiled = $this->compiled($templateName).'.php';
        if (!file_exists($compiled) || $this->expired($compiled, $tempContents)) {
            $this->createStorageDirectories();
            file_put_contents($compiled, $this->compile($tempContents));
        }
        if ($data) {
            foreach ($data as $index => $value) {
                $this->vars[$index] = $value;
            }
        }
        extract($this->vars, EXTR_SKIP);
        include_once $compiled;
    }

    protected function compile($path)
    {
        return $this->compileFile(file_get_contents($path), $path);
    }

    protected function compileFile($string, $path)
    {

        foreach ($this->compilers as $compiler) {
			$method = "compile_{$compiler}";

			$string = $this->$method($string, $path);
		}
		return $string;
    }

    protected function expired($path, $file)
    {
        if (filemtime($path) < filemtime($file)) {
            return true;
        }
        return false;
    }

    protected function compiled($path = null)
    {
        return path('storage').'/hax/'.md5($path);
    }

    public function renderArray ($fileName, $dataAr) 
    { 
        $rendered = "";
        if (count($dataAr && is_array($dataAr))) {
            foreach ($dataAr as $data) {
                $rendered.= $this->display($fileName, $data);
            }
        }
        return $rendered;
    
    }

    protected function compile_opening($value)
	{
		$pattern = '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/';

		return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
    }
    
    protected function compile_closing($value)
	{
		$pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
    }
    
    protected function compile_else($value)
	{
		return preg_replace('/(\s*)@(else)(\s*)/', '$1<?php $2: ?>$3', $value);
    }
    
    protected function compile_echo($value)
	{
		return preg_replace('/\{\{(.+?)\}\}/', '<?php echo ' . htmlentities('$1') . '; ?>', $value);
    }

    protected function compile_echo_escape($value)
	{
		return preg_replace('/{{!!(.+?)!!}}/', '<?php echo $1; ?>', $value);
    }

    protected function compile_tags($value)
    {
        return preg_replace('~{%(.+?)}~', '<?php $1 ?>', $value);
    }

    protected function compile_tags_opening($value)
    {
        return preg_replace('~{%~', '<?php', $value);
    }

    protected function compile_tags_closing($value)
    {
        return preg_replace('~%}~', '?>', $value);
    }
    
    protected function compile_yield($value)
	{
		$pattern = $this->matches('yield');
		return preg_replace($pattern, '$1<?php $this->emptySection $2; ?>', $value);
    }
    
    protected function matches($function)
	{
		return '/(\s*)@' . $function . '(\s*\(.*\))/';
    }
    
    protected function compile_section($value)
	{
		$pattern = $this->matches('section');

		return preg_replace($pattern, '$1<?php $this->section $2; ?>', $value);
    }
    
    protected function compile_endsection($value)
	{
		$pattern = '/(\s*)@(endsection)(\s*)/';

		return preg_replace($pattern, '$1<?php $this->endSection() ?>', $value);
    }

    protected function compile_forelse($value)
	{
		preg_match_all('/(\s*)@forelse(\s*\(.*\))(\s*)/', $value, $matches);

		foreach ($matches[0] as $forelse) {
			preg_match('/\s*\(\s*(\S*)\s/', $forelse, $variable);

			$if = "<?php if (count({$variable[1]}) > 0): ?>";

			$search = '/(\s*)@forelse(\s*\(.*\))/';

			$replace = '$1'.$if.'<?php foreach$2: ?>';

			$hax = preg_replace($search, $replace, $forelse);

			$value = str_replace($forelse, $hax, $value);
		}

		return $value;
    }
    
    protected function compile_endforelse($value)
	{
		return str_replace('@endforelse', '<?php endif; ?>', $value);
	}

    protected function compile_extends($value)
	{
        $pattern = $this->matches('extends');
        
        return preg_replace($pattern, '$1<?php $this->extend $2; ?>', $value);
    }

    protected function compile_include($value)
    {
        $pattern = $this->matches('include');
        
        return preg_replace($pattern, '$1<?php $this->display $2; ?>', $value);
    }

    protected function compile_parent($value)
    {
        $pattern = '/(\s*)@(parent)(\s*)/';
        
        return preg_replace($pattern, '$1<?php $this->parentSection() ?>', $value);
    }
    
    
}