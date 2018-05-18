<?php
namespace Yuga\Views\Widgets\Html;

use Yuga\Http\Middleware\BaseCsrfVerifier;

class HtmlForm extends Html
{

    const ENCTYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';
    const ENCTYPE_FORM_DATA = 'multipart/form-data';
    const ENCTYPE_TEXT_PLAIN = 'text/plain';

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    public function __construct($name = null, $method = self::METHOD_POST, $action = null, $encoding = self::ENCTYPE_APPLICATION_URLENCODED)
    {
        parent::__construct('form');

        //$this->closingType = static::CLOSE_TYPE_NONE;
        if ($name) {
            $this->name($name);
        }
        
        $this->enctype($encoding);
        $this->method($method);
        $this->action(($action === null) ? route() : $action);

        // Add csrf token
        if (strtolower($method) !== 'get') {
            $this->addInnerHtml("\n".(new HtmlInput('hidden', BaseCsrfVerifier::POST_KEY, csrf_token()))->setClosingType(static::CLOSE_TYPE_SHORT)."\n");
        }
    }

    public function name($name)
    {
        return $this->addAttribute('name', $name);
    }

    public function method($method)
    {
        if ($method !== static::METHOD_GET) {
            return $this->addAttribute('method', $method);
        }

        return $this;
    }

    public function enctype($enctype)
    {
        if ($enctype !== static::ENCTYPE_APPLICATION_URLENCODED) {
            return $this->addAttribute('enctype', $enctype);
        }

        return $this;
    }

    public function fileUpload()
    {
        return $this->enctype(static::ENCTYPE_FORM_DATA);
    }

    public function action($action)
    {
        return $this->addAttribute('action', $action);
    }

}