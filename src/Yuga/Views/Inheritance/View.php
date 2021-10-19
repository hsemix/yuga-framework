<?php

namespace Yuga\Views\Inheritance;

use Exception;

$GLOBALS['_smx_base'] = null;
$GLOBALS['_smx_stack'] = null;

class View
{
    const EXT = '.php';
    const HAX = '.hax.php';
    protected $template_dir = 'resources/views/';
    protected $template_cache_dir = './storage/hax/';
    protected $vars = [];
    protected $dataView = []; 
    
    public function setTemplateDirectory($dir = 'resources/views')
    {
        $this->template_dir = $dir.'/';
        if (!is_dir($directory = path($dir))) {
            mkdir($directory, 0755, true);
        }
        return $this;
    }

    public function getTemplateDirectory()
    {
        return path($this->template_dir);
    }

    public function renderView($template_file, $data = false) 
    {
        if ($data) {
            $this->dataView = $data;
            foreach ($data as $index => $value) {
                $this->vars[$index] = $value;
            }
        }
        
        $rendered = "";
        
        $exceptionFile = str_replace('.', '/', $template_file);

        if (file_exists($this->getTemplateDirectory().$exceptionFile.self::HAX)) {
            return $this->renderHaxTemplate($template_file);
        } elseif (file_exists($this->getTemplateDirectory().$exceptionFile.self::EXT)) {
            extract($this->vars);
            ob_start();
            require_once $this->getTemplateDirectory().$exceptionFile.self::EXT;
            $rendered = ob_get_contents(); 
            ob_end_flush();  
        } else {
            
            throw new Exception('no template file ' . $exceptionFile.self::EXT . ' present in directory ' . $this->template_dir);
        }
        $this->dataView = [];
    }

    public function render($view, $data = false)
    {
        $evaluatedView = str_replace('.', '/', $view);
        if (file_exists($this->getTemplateDirectory().$evaluatedView.self::HAX)) {
            ob_start();
            $this->renderHaxTemplate($evaluatedView, $data);
            $rendered = ob_get_contents(); 
            ob_end_clean();
            return $rendered;
        } elseif (file_exists($this->getTemplateDirectory().$evaluatedView.self::EXT)) {
            ob_start();
            $this->renderView($view, $data);
            $rendered = ob_get_contents(); 
            ob_end_clean();
            return $rendered;
        } else {
            throw new Exception('no template file ' . $this->getTemplateDirectory().$evaluatedView.self::EXT . ' present in directory ' . $this->template_dir);
        }
    }
    
    public function __set($name, $value) 
    {
        $this->vars[$name] = $value;
    }
    
    public function __get($name) 
    {
        if (isset($this->dataView[$name])) {  
            return $this->dataView[$name];
        } else {
            return $this->vars[$name];
        }
    }

    public function extend($template = null)
    {
        if ($template) {
            $template = str_replace(".", "/", $template);

            if (file_exists($this->getTemplateDirectory().$template.self::HAX)) {
                // compile the hax template and include it if exists
                $file = $this->renderHaxTemplate($template);
            } elseif (file_exists($this->getTemplateDirectory().$template.self::EXT)) {
                $file = $this->getTemplateDirectory().$template.self::EXT;
                include_once($file);
            } else {
                $file = $this->getTemplateDirectory().$template;
                throw new \Exception("The file {$file} doesnot exist"); 
            }
            
        }
    }

    public function section($name, $filters=null)
    {
        $trace = $this->callingTrace();
        $this->init($trace);
        $stack =& $GLOBALS['_smx_stack'];
        $stack[] = $this->newSection($name, $filters, $trace);
        
    }
    public function fetch()
    {
        
    }

    private function callingTrace() 
    {
        $trace = debug_backtrace();
    
        foreach ($trace as $i => $location) {
            if (isset($location['file'])) {
                if ($location['file'] !== __FILE__) {
                    return array_slice($trace, $i);
                }
            } 
        }
    }

    private function init($trace) 
    {
        $base =& $GLOBALS['_smx_base'];
        if ($base && ! $this->inBaseOrChild($trace)) {
            $this->flushSections(); // will set $base to null
        }
        if (!$base) {
            $base = [
                'trace' => $trace,
                'filters' => null, // purely for compile
                'children' => [],
                'start' => 0, // purely for compile
                'end' => null
            ];
            $GLOBALS['_smx_level'] = ob_get_level();
            $GLOBALS['_smx_stack'] = [];
            $GLOBALS['_smx_hash'] = [];
            $GLOBALS['_smx_end'] = null;
            $GLOBALS['_smx_after'] = '';
            ob_start('\Yuga\Views\Inheritance\View::bufferCallback');
        }
    }

    public function newSection($name, $filters, $trace) 
    {
        $base =& $GLOBALS['_smx_base'];
        $stack =& $GLOBALS['_smx_stack'];
        while ($section = end($stack)) {
            if ($this->isSameFile($section['trace'], $trace)) {
                break;
            } else {
                array_pop($stack);
                $this->insertSection($section);
                $this->warning(
                    "missing endSection() for section('{$section['name']}')",
                    $this->callingTrace(),
                    $section['trace']
                );
            }
        }
        if ($base['end'] === null && !$this->inBase($trace)) {
            $base['end'] = ob_get_length();
        }
        if ($filters) {
            if (is_string($filters)) {
                $filters = preg_split('/\s*[,|]\s*/', trim($filters));
            }
            else if (!is_array($filters)) {
                $filters = [$filters];
            }
            foreach ($filters as $i => $f) {
                if ($f && !is_callable($f)) {
                    $this->warning(
                        is_array($f) ?
                            "filter " . implode('::', $f) . " is not defined":
                            "filter '$f' is not defined", // TODO: better messaging for methods
                        $trace
                    );
                    $filters[$i] = null;
                }
            }
        }
        return [
            'name' => $name,
            'trace' => $trace,
            'filters' => $filters,
            'children' => [],
            'start' => ob_get_length()
        ];
    }

    public function endSection($name=null) 
    {
        $trace = $this->callingTrace();
        $this->init($trace);
        $stack =& $GLOBALS['_smx_stack'];
        if ($stack) {
            $section = array_pop($stack);
            if($name && $name != $section['name']) {
                $this->warning("section('{$section['name']}') does not match endSection('$name')", $trace);
            }
            $this->insertSection($section);
        } else {
            $this->warning(
                $name ? "orphan endSection('$name')" : "orphan endSection()",
                $trace
            );
        }
    }

    private function insertSection($section) 
    { 
        // at this point, $section is done being modified
        $base =& $GLOBALS['_smx_base'];
        $stack =& $GLOBALS['_smx_stack'];
        $hash =& $GLOBALS['_smx_hash'];
        $end =& $GLOBALS['_smx_end'];
        $section['end'] = $end = ob_get_length();
        $name = $section['name'];
        if ($stack || $this->inBase($section['trace'])) {
            $section_anchor = [
                'start' => $section['start'],
                'end' => $end,
                'section' => $section
            ];
            if ($stack) {
                // nested section
                $stack[count($stack)-1]['children'][] =& $section_anchor;
            } else {
                // top-level section in base
                $base['children'][] =& $section_anchor;
            }
            $hash[$name] =& $section_anchor; // same reference as children array
        } else if (isset($hash[$name])) {
            if ($this->isSameFile($hash[$name]['section']['trace'], $section['trace'])) {
                $this->warning(
                    "cannot define another section called '$name'",
                    $this->callingTrace(),
                    $section['trace']
                );
            } else {
                // top-level section in a child template; override the base's section
                $hash[$name]['section'] = $section;
            }
        }
    }
    private function inBase($trace) 
    {
        return $this->isSameFile($trace, $GLOBALS['_smx_base']['trace']);
    }

    private function isSameFile($trace1, $trace2) 
    {
        return
            $trace1 && $trace2 &&
            $trace1[0]['file'] === $trace2[0]['file'] &&
            array_slice($trace1, 1) === array_slice($trace2, 1);
    }
    private function inBaseOrChild($trace) 
    {
        $base_trace = $GLOBALS['_smx_base']['trace'];
        return
            $trace && $base_trace &&
            $this->isSubtrace(array_slice($trace, 1), $base_trace) &&
            $trace[0]['file'] === $base_trace[count($base_trace)-count($trace)]['file'];
    }

    private function isSubtrace($trace1, $trace2) 
    { 
        // is trace1 a subtrace of trace2
        $len1 = count($trace1);
        $len2 = count($trace2);
        if ($len1 > $len2) {
            return false;
        }
        for ($i=0; $i<$len1; $i++) {
            if ($trace1[$len1-1-$i] !== $trace2[$len2-1-$i]) {
                return false;
            }
        }
        return true;
    }
    private function flushSections() 
    {
        $base =& $GLOBALS['_smx_base'];
        if ($base) {
            $stack =& $GLOBALS['_smx_stack'];
            $level =& $GLOBALS['_smx_level'];
            while ($section = array_pop($stack)) {
                $this->warning(
                    "missing endSection() for section('{$section['name']}')",
                    $this->callingTrace(),
                    $section['trace']
                );
            }
            while (ob_get_level() > $level) {
                ob_end_flush(); // will eventually trigger bufferCallback
            }
            $base = null;
            $stack = null;
        }
    }


    private function sectionBase() 
    {
        $this->init($this->callingTrace());
    }
    private function bufferCallback($buffer) 
    {
        $base =& $GLOBALS['_smx_base'];
        $stack =& $GLOBALS['_smx_stack'];
        $end =& $GLOBALS['_smx_end'];
        $after =& $GLOBALS['_smx_after'];
        if ($base) {
            while ($section = array_pop($stack)) {
                $this->insertSection($section);
                $this->warning(
                    "missing endSection() for section('{$section['name']}')",
                    $this->callingTrace(),
                    $section['trace']
                );
            }
            if ($base['end'] === null) {
                $base['end'] = strlen($buffer);
                $end = null; // todo: more explanation
                // means there were no sections other than the base's
            }
            $parts = $this->compile($base, $buffer);
            // remove trailing whitespace from end

            if($i = count($parts) - 1){
                if (isset($parts[$i]))
                    $parts[$i] = rtrim($parts[$i]);
            }
            
            // if there are child template sections, preserve output after last one
            if ($end !== null) {
                $parts[] = substr($buffer, $end);
            }
            // for error messages
            $parts[] = $after;
            return implode($parts);
        } else {
            return '';
        }
    }

    private function warning($message, $trace, $warning_trace=null) 
    {
        if (error_reporting() & E_USER_WARNING) {
            if (defined('STDIN')) {
                // from command line
                $format = "\nWarning: %s in %s on line %d\n";
            } else {
                // from browser
                $format = "<br />\n<b>Warning</b>:  %s in <b>%s</b> on line <b>%d</b><br />\n";
            }
            if (!$warning_trace) {
                $warning_trace = $trace;
            }
            $s = sprintf($format, $message, $warning_trace[0]['file'], $warning_trace[0]['line']);
            if (!$GLOBALS['_smx_base'] || $this->inBase($trace)) {
                echo $s;
            } else {
                $GLOBALS['_smx_after'] .= $s;
            }
        }
    }

    private function compile($section, $buffer) 
    {
        $parts = [];
        $previ = $section['start'];
        foreach ($section['children'] as $child_anchor) {
            $parts[] = substr($buffer, $previ, $child_anchor['start'] - $previ);
            $parts = array_merge(
                $parts,
                $this->compile($child_anchor['section'], $buffer)
            );
            $previ = $child_anchor['end'];
        }
        if ($previ != $section['end']) {
            // could be a big buffer, so only do substr if necessary
            $parts[] = substr($buffer, $previ, $section['end'] - $previ);
        }
        if ($section['filters']) {
            $s = implode($parts);
            foreach ($section['filters'] as $filter) {
                if ($filter) {
                    $s = call_user_func($filter, $s);
                }
            }
            return [$s];
        }
        return $parts;
    }
    public function emptySection($name)
    {
        $trace = $this->callingTrace();
        $this->init($trace);
        $this->insertSection(
            $this->newSection($name, null, $trace)
        );
    }

    public function parentSection() 
    {
        if ($GLOBALS['_smx_stack']) {
            echo $this->getParentSection();
        } else {
            $this->warning(
                "Yuga\Views\Inheritance\View::parentSection() call must be within a section",
                $this->callingTrace()
            );
        }
    }


    private function getParentSection() 
    {
        $stack =& $GLOBALS['_smx_stack'];
        if ($stack) {
            $hash =& $GLOBALS['_smx_hash'];
            $section = end($stack);
            if (isset($hash[$section['name']])) {
                return implode(
                    $this->compile(
                        $hash[$section['name']]['section'],
                        ob_get_contents()
                    )
                );
            }
        } else {
            $this->warning(
                "Yuga\Views\Inheritance\View::getParentSection() call must be within a section",
                $this->callingTrace()
            );
        }
        return null;
    }
}