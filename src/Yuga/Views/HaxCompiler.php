<?php

namespace Yuga\Views;

use Closure;
use Yuga\App;
use Yuga\Support\Arr;
use Yuga\Http\Request;
use Yuga\Validate\Message;
use Yuga\Views\Inheritance\View;

class HaxCompiler extends View
{
    private $hax = '.hax.php';
    /**
     * Array of opening and closing tags for echos.
     *
     * @var array
     */
    protected $contentTags = ['{{', '}}'];

    /**
     * Array of opening and closing tags for escaped echos.
     *
     * @var array
     */
    protected $escapedTags = ['{{{', '}}}'];

    /**
     * Counter to keep track of nested forelse statements.
     *
     * @var int
     */
    protected $forelseCounter = 0;

    protected $compilers = [ 
        'yield',
        'section',
        'endSection',
        'extends',
        'include',
        'parent',
        'statements',
        'comments',
        'echos',
        'extensions'
    ];

    /**
     * All of the registered extensions.
     *
     * @var array
     */
    protected $extensions = [];
    /**
     * All values registered in extensions
     */
    protected $extensionValues = [];

    protected $extensionKeys = [];
    
    public function __construct($template_dir = NULL) 
    {
        if ($template_dir) {
            $this->template_dir = $template_dir;
        }
        $this->vars['session'] = App::make('session');
        $this->vars['request'] = new Request;
        $this->vars['errors'] = new Message;
        if (!app()->runningInConsole()) {
            if ($this->session->exists('errors')) {
                $this->vars['errors'] = $this->session->get('errors');
            }
        }
        

        event('on:hax-instance', ['compiler' => $this]);
    }

    public function display($temp, array $data = null)
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
        $tempContents = $this->getTemplateDirectory() . $templateName.$this->hax;
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
        include $compiled;
    }

    protected function compile($path)
    {
        return $this->compileFile(file_get_contents($path), $path);
    }

    protected function compileFile($string, $path)
    {
        foreach ($this->compilers as $compiler) {
			$method = "compile" . ucfirst($compiler);

			$string = call_user_func([$this, $method], $string);
            
		}
        event('on:hax-extension', ['extensions' => ['values' => $this->extensionValues, 'keys' => $this->extensionKeys]]);
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
        return path('storage') . '/hax/' . md5($path);
    }

    /**
     * Execute the user defined extensions.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileExtensions($value)
    {
        foreach ($this->extensions as $compiler) {
            $value = call_user_func($compiler, $value, $this);
            $this->extensionValues[] = $compiler;
        }

        return $value;
    }

    /**
     * Compile Template comments into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileComments($value)
    {
        $pattern = sprintf('/%s--((.|\s)*?)--%s/', $this->contentTags[0], $this->contentTags[1]);

        return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
    }

    /**
     * Compile Template echos into valid PHP.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEchos($value)
    {
        $difference = strlen($this->contentTags[0]) - strlen($this->escapedTags[0]);

        if ($difference > 0) {
            return $this->compileEscapedEchos($this->compileRegularEchos($value));
        }

        return $this->compileRegularEchos($this->compileEscapedEchos($value));
    }

    /**
     * Compile Template Statements that start with "@"
     *
     * @param  string  $value
     * @return mixed
     */
    protected function compileStatements($value)
    {
        $callback = function($match) {
            if (method_exists($this, $method = 'compile' .ucfirst($match[1]))) {
                $match[0] = call_user_func([$this, $method], Arr::get($match, 3));
            }

            return isset($match[3]) ? $match[0] : $match[0] .$match[2];
        };

        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', $callback, $value);
    }

    /**
     * Compile the foreach statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileForeach($expression)
    {
        return "<?php foreach {$expression}: ?>";
    }

    /**
     * Compile the end-for-each statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndforeach($expression)
    {
        return "<?php endforeach; ?>";
    }

    /**
     * Compile the while statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileWhile($expression)
    {
        return "<?php while {$expression}: ?>";
    }

    /**
     * Compile the end-while statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndwhile($expression)
    {
        return "<?php endwhile; ?>";
    }

    /**
     * Compile the for statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileFor($expression)
    {
        return "<?php for {$expression}: ?>";
    }

    /**
     * Compile the end-for statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndfor($expression)
    {
        return "<?php endfor; ?>";
    }

    /**
     * Compile the else statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElse($expression)
    {
        return "<?php else: ?>";
    }

    /**
     * Compile the forelse statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileForelse($expression)
    {
        $empty = '$__empty_' . ++$this->forelseCounter;

        return "<?php {$empty} = true; foreach{$expression}: {$empty} = false; ?>";
    }

    /**
     * Compile the end-for-else statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndforelse($expression)
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile the forelse statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEmpty($expression)
    {
        $empty = '$__empty_' . $this->forelseCounter--;

        return "<?php endforeach; if ({$empty}): ?>";
    }

    /**
     * Compile the break statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileBreak($expression)
    {
        return $expression ? "<?php if {$expression} break; ?>" : '<?php break; ?>';
    }

    /**
     * Compile the continue statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileContinue($expression)
    {
        return $expression ? "<?php if {$expression} continue; ?>" : '<?php continue; ?>';
    }

    /**
     * Compile the if statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileIf($expression)
    {
        return "<?php if {$expression}: ?>";
    }

    /**
     * Compile the else-if statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileElseif($expression)
    {
        return "<?php elseif {$expression}: ?>";
    }

    /**
     * Compile the end-can statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndcan($expression)
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-cannot statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndcannot($expression)
    {
        return '<?php endif; ?>';
    }

    /**
     * Compile the end-if statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndif($expression)
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile the raw PHP statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compilePhp($expression)
    {
        return !empty($expression) ? "<?php {$expression}; ?>" : '<?php ';
    }

    /**
     * Compile end-php statement into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndphp($expression)
    {
        return ' ?>';
    }

    /**
     * Compile the unset statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileUnset($expression)
    {
        return "<?php unset{$expression}; ?>";
    }

    /**
     * Compile the unless statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileUnless($expression)
    {
        return "<?php if (!$expression): ?>";
    }

    /**
     * Compile the end unless statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileEndunless($expression)
    {
        return "<?php endif; ?>";
    }

    /**
     * Compile the "regular" echo statements.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileRegularEchos($value)
    {
        $pattern = sprintf('/(@)?%s\s*(.+?)\s*%s(\r?\n)?/s', $this->contentTags[0], $this->contentTags[1]);

        $callback = function($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] .$matches[3];

            return $matches[1] ? substr($matches[0], 1) : '<?php echo ' .$this->compileEchoDefaults($matches[2]) .'; ?>' .$whitespace;
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the escaped echo statements.
     *
     * @param  string  $value
     * @return string
     */
    protected function compileEscapedEchos($value)
    {
        $pattern = sprintf('/%s\s*(.+?)\s*%s(\r?\n)?/s', $this->escapedTags[0], $this->escapedTags[1]);

        $callback = function($matches)
        {
            $whitespace = empty($matches[2]) ? '' : $matches[2] .$matches[2];

            return '<?php echo e(' . $this->compileEchoDefaults($matches[1]) .'); ?>' . $whitespace;
        };

        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * Compile the default values for the echo statement.
     *
     * @param  string  $value
     * @return string
     */
    public function compileEchoDefaults($value)
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    protected function matches($function)
	{
		return '/(\s*)@' . $function . '(\s*\(.*\))/';
    }

    protected function compileYield($value)
	{
		$pattern = $this->matches('yield');
		return preg_replace($pattern, '$1<?php $this->emptySection $2; ?>', $value);
    }

    protected function compileSection($value)
	{
		$pattern = $this->matches('section');

		return preg_replace($pattern, '$1<?php $this->section $2; ?>', $value);
    }
    
    protected function compileEndSection($value)
	{
		$pattern = '/(\s*)@(endsection)(\s*)/';

		return preg_replace($pattern, '$1<?php $this->endSection() ?>', $value);
    }

    protected function compileExtends($value)
	{
        $pattern = $this->matches('extends');
        
        return preg_replace($pattern, '$1<?php $this->extend $2; ?>', $value);
    }

    protected function compileInclude($value)
    {
        $pattern = $this->matches('include');
        
        return preg_replace($pattern, '$1<?php $this->display $2; ?>', $value);
    }

    protected function compileParent($value)
    {
        $pattern = '/(\s*)@(parent)(\s*)/';
        
        return preg_replace($pattern, '$1<?php $this->parentSection() ?>', $value);
    }

    /**
     * Register a custom Template compiler.
     *
     * @param  \Closure  $compiler
     * @return void
     */
    public function extension(Closure $compiler)
    {
        $this->extensions[] = $compiler;
        return $this;
    }

    /**
     * Create a plain Template matcher.
     *
     * @param  string  $function
     * @return string
     */
    public function replaceWith($pattern, $with, $value)
    {
        return preg_replace($pattern, '$1' . $with, $value);
    }

    public function patternMatches($pattern)
    {
        return $this->matches($pattern);
    }

    public function patternMatchesFunction($pattern)
    {
        $this->extensionKeys[] = $pattern;
        return $this->patternMatches($pattern);
    }

    public function patternMatchesString($string)
    {
        $this->extensionKeys[] = $string;
		return '/(\s*)@(' . $string . ')(\s*)/';    
    }
}