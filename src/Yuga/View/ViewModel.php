<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\View;

use Exception;
use Yuga\Views\UI\Site;
use Yuga\Views\Widgets\Form\Form;
use Yuga\Views\Widgets\Html\Html;

class ViewModel extends BaseView
{
    const EXT = '.php';
    protected $form;
    protected $template;
    protected $data = [];
    protected $contentTemplate;
    protected $templateHtml;
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('Default');
        $this->setContentTemplate($this->getTemplatePath());
        $this->form = new Form;
    }

    public function __set($name, $value) 
    {
        $this->data[$name] = $value;
    }

    public function __get($name) 
    {
        return $this->data[$name];
    }

    protected function setTemplate($path = null, $relative = true)
    {
        if ($path) {
            $path = str_replace('.', '/', $path);
            $this->template = path(DIRECTORY_SEPARATOR.'views' . DIRECTORY_SEPARATOR . $path.self::EXT);
        } else {
            $this->template = '';
        }
    }

    protected function getTemplate()
    {
        return $this->template;
    }

    protected function getTemplatePath()
    {
        $path = array_slice(explode('\\', static::class), 2);
        
        return path(DIRECTORY_SEPARATOR.'views/templates/' . join(DIRECTORY_SEPARATOR, $path) . self::EXT);
    }

    protected function setContentTemplate($template)
    {
        $this->contentTemplate = $template;
    }

    protected function getContentTemplate()
    {
        return $this->contentTemplate;
    }

    protected function setTemplateHtml($html)
    {
        $this->templateHtml = $html;
    }

    protected function getTemplateHtml()
    {
        return $this->templateHtml;
    }

    public function form()
    {
        return $this->form;
    }

    public function viewModel(ViewModel $viewModel)
    {
        if ($viewModel->getTemplate() === $this->getTemplate()) {
            $viewModel->setTemplate(null);
        }
        return $viewModel;
    }

    public function display($file)
    {
        $file = str_replace('.', '/', $file);
        require path('views/'.$file . self::EXT);
    }

    protected function renderContent()
    {
        debug('START: rendering content-template: ' . $this->contentTemplate);
        
        
        if ($this->templateHtml === null && $this->contentTemplate !== null) {
            extract($this->data);
            ob_start();
            include $this->contentTemplate;
            $this->templateHtml = ob_get_contents();
            ob_end_clean();
        }

        debug('END: rendering content-template: ' . $this->templateHtml);
    }

    protected function renderTemplate()
    {
        debug('START: rendering template: ' . $this->template);

        if ($this->template !== '') {
            ob_start();
            include $this->template;
            $this->templateHtml = ob_get_contents();
            ob_end_clean();
        }

        debug('END: rendering template ' . $this->template);
    }

    public function printCss($section = Site::SECTION_DEFAULT)
    {
        $output = '';
        foreach ($this->getSite()->getCss($section) as $css) {
            $output .= (new Html('link'))
                ->setClosingType(Html::CLOSE_TYPE_SHORT)
                ->attr('href', $css)
                ->attr('rel', 'stylesheet')."\n";
        }
        return $output;
    }

    public function printJs($section = Site::SECTION_DEFAULT)
    {
        $output = '';
        foreach ($this->getSite()->getJs($section) as $js) {
            $output .= (new Html('script'))->attr('src', $js)."\n";
        }
        return $output;
    }


    public function __toString()
    {
        try {
            return $this->render();
          } catch (Exception $e) {
              echo $e->getMessage();
          }
          return '';
    }

    public function render()
    {
        $this->renderContent();
        $this->renderTemplate();
        //$this->messages->clear();
        debug('END ViewModel: ' . static::class);
        return $this->templateHtml;
    }

    public function showMessagess($type, $placement = null)
    {
        $placement = ($placement === null) ? $this->defaultMessagePlacement : $placement;

        if ($this->hasMessages($type, $placement)) {
            $o = sprintf('<div class="alert alert-%s">', $type);

            $msg = [];
            foreach ($this->getMessages($type, $placement) as $error) {
                $msg[] = $error->getMessage();
            }

            return $o . join('<br>', $msg) . '</div>';
        }

        return '';
    }

    protected function showMessages($type, $placement = null)
    {
        $placement = ($placement === null) ? $this->defaultMessagePlacement : $placement;
        if ($this->hasMessages($type, $placement)) {
            $message = sprintf('<div class="alert alert-%s">', $type);
            $msg = [];
            foreach ($this->getMessages($type, $placement) as $error) {
                $msg[] = $error->getMessage();
            }
            return $message . implode('<br>', $msg) . '</div>';
        }
        return '';
    }

    public function showFlash($placement = null)
    {
        $o = $this->showMessages($this->errorType, $placement);
        $o .= $this->showMessages('warning', $placement);
        $o .= $this->showMessages('info', $placement);
        $o .= $this->showMessages('success', $placement);

        return $o;
    }
}