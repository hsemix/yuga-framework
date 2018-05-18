<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\View;

use Yuga\Validate\Message;
use Yuga\Shared\Controller as SharedController;
class BaseView
{
    use SharedController;
    protected $errors;
    protected $message;
    protected $errorType = 'danger';
    protected $defaultMessagePlacement = 'default';
    public function __construct()
    {
        $this->init();
        if ($this->session->exists('errors')) {
            $this->errors = $this->session->get('errors');
        } else {
            $this->errors = new Message;
        }
        $this->message = $this->errors;
    }

    /**
    * TO DO: handle automatic validation later
    */

    protected function setError($message, $placement = null)
    {
        $this->setMessage($message, $this->errorType, $placement);
    }
    public function getMessages($type, $placement = null)
    {
        // Trigger validation
        $this->performValidation();
        $type = ($type == 'danger') ? 'errors' : $type;
        $messages = [];
        $search = $this->message->get($type);

        if ($search !== null) {
            foreach ($search as $message) {
                if ($placement === null) {
                    $messages[] = $message;
                }
            }
        }
        return $messages;
    }

    protected function performValidation()
    {
        return $this->message;
    }

    public function hasMessages($type, $placement = null)
    {
        return (bool)count($this->getMessages($type, $placement));
    }

    protected function setMessage($message, $type, $placement = null, $index = null)
    {
        $msg = new FormMessage();
        $msg->setMessage($message);
        $msg->setPlacement(($placement === null) ? $this->defaultMessagePlacement : $placement);
        $msg->setIndex($index);
        $this->messages->set($msg, $type);
    }
    // auto validation ends

    /**
     * Get the site instance as use it
     */

    public function getSite()
    {
        return app()->site;
    }

    /**
     * Determine whether the request is ajax or not
     * 
     * @param null
     * 
     * @return \boolean
     */

    public function isAjaxRequest()
    {
        return (request()->getHeader('http-x-requested-with') !== null && strtolower(request()->getHeader('http-x-requested-with')) === 'xmlhttprequest');
    }

    /**
     * Append some text to the current Site Title
     * 
     * @param \string $title
     * @param \string $separator
     * 
     * @return null
     */

    protected function appendSiteTitle($title, $separator = '-')
    {
        $separator = ($separator === null) ? '' : ' ' . $separator . ' ';
        app()->site->setTitle(app()->site->getTitle() . $separator . $title);
    }


    /**
     * Prepend some text to the current Site Title
     * 
     * @param \string $title
     * @param \string $separator
     * 
     * @return null
     */

    protected function prependSiteTitle($title, $separator = ' - ')
    {
        app()->site->setTitle($title . $separator . app()->site->getTitle());
    }

    /**
     * Determine whether the route was defined with the form method i.e. Route::form('/test')
     * 
     * @param null
     * 
     * @return \boolean
     */
    public function isPostBack()
    {
        return (bool)(request()->getMethod() !== 'get');
    }

    protected function validate($rules = [])
    {
        $fields = $this->request->getInput()->all();
        unset($fields['_token']);
        $validation = $this->validate->check($this->request->getInput()->all(), $rules);
        if ($validation->failed()) {
            if ($this->request->isAjax()) {
                return $validation->errors();
            } else {
                $this->session->put('errors', $validation->errors());
                $this->request->addOld();
                return $this->response->refresh();
            } 
        }
        $this->session->delete('old-data');
        return $validation->getValidated();
    }

}