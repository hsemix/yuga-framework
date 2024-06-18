<?php
namespace Yuga\Controllers;

use Yuga\Http\Request;
use Yuga\Validate\Message;
use Yuga\Shared\Controller as SharedController;

class Controller
{
    use SharedController;

    public function __construct()
    {
        $this->init();
    }

    public function validate(Request $request, $rules = [], $clearOldData = true)
    {
        $fields = $request->getInput()->all();
        unset($fields['_token']);
        $this->validateFiles($rules);
        $validation = $this->validate->check($request->getInput()->all(), $rules);
        if ($validation->failed()) {
            if ($request->isAjax()) {
                return $validation->errors();
            } else {
                $this->session->put('errors', $validation->errors());
                $this->request->addOld();
                return $this->response->redirect->back();
            } 
        }
        if ($clearOldData) {
            $this->session->delete('old-data');
        }
        return $validation->getValidated();
    }

    protected function validateFiles(array $rules = [])
    {
        if (count($rules) > 1) {
            $this->validateFilesMoreRules($rules);
        }else {
            $this->validateFilesSingleRule($rules);
        }
        
    }

    protected function validateFilesMoreRules(array $rules = [])
    {
        foreach ($rules as $field => $rules) {
            if ($rules === 'file') {
                
                if (!$this->request->hasFile($field)) {
                    $this->session->put('file-message', "The field {$field} requires an uploadable file");
                    $this->session->put('yuga-file-field', $field);
                }
            }
        }
    }

    protected function validateFilesSingleRule(array $rules = [])
    {
        $message = new Message();
        foreach ($rules as $field => $rules) {
            if ($rules === 'file') {
                if (!$this->request->hasFile($field)) {
                    $message->addMessage("The field {$field} requires an uploadable file", $field);
                }
            }
        }
        if ($message->hasMessages()) {
            $this->session->put('errors', $message);
            $this->request->addOld();
            return $this->response->redirect->back();
        }
    }
}