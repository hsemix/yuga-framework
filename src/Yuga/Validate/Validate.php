<?php
namespace Yuga\Validate;

use App;
use Closure;
use Exception;
use Yuga\Http\Request;
use Yuga\Http\Response;
use Yuga\Session\Session;

class Validate
{
    protected $app;
    protected $items;
    protected $message;
    protected $rules = [
        'required',
        'min',
        'max',
        'email',
        'alnum',
        'matches',
        'unique',
        'in',
        'file',
        'int',
        'string',
        'confirmed',
    ];

    public $messages = [
        'required'  => '{field} is required!',
        'min'       => '{field} must be a minimum of {satisfy} characters',
        'max'       => '{field} must be a maximum of {satisfy} characters',
        'email'     => '{field} {value} is not a valid email address',
        'alnum'     => '{field} must contain letters and numbers only',
        'matches'   => '{field} must match {satisfy}',
        'unique'    => '{field} already exists',
        'in'        => '{field} must be in {satisfy}',
        'file'      => '{field} must be an uploadable file',
        'int'       => '{field} must be a number',
        'string'    => '{field} must be a string',
        'confirmed' => '{field} Confirmation does not match'
    ];

    public $messagesArray = [
        'required'  => '{field} are required!',
        'min'       => 'All of ({value}) must be a minimum of {satisfy} characters',
        'max'       => 'All of ({value}) must be a maximum of {satisfy} characters',
        'email'     => 'One of ({value}) is not a valid email address',
        'alnum'     => 'All of ({value}) must contain letters and numbers only',
        'matches'   => 'All of ({value}) must match {satisfy}',
        'unique'    => 'One of ({value}) already exists',
        'in'        => 'All of ({value}) must be in {satisfy}',
        'file'      => '{field} must be an uploadable file',
        'int'       => 'All of ({value}) must be numbers',
        'string'    => 'All of ({value}) must be strings',
        'confirmed' => '{field} Confirmation do not match'
    ];

    public $fieldMessages = [];
    public $customRules = [];
    protected $response;
    protected $session;
    protected $fields = [];
    protected $fieldRules = [];
    public function __construct(Message $message, Response $response, Session $session, App $app, Request $request)
    {
        $this->app = $app;
        $this->message = $message;
        $this->response = $response;
        $this->session = $session;
        $this->request = $request;
    }

    public function check(array $items, $rules)
    {
        $this->fieldRules = $rules;
        $this->items = $items;
        foreach ($items as $item => $value) {
            $processedRules = $this->processRules($rules);
            if (in_array(is_array($item)? $item[0] : $item, $processedRules['fields'])) {
                $this->fields[$item] = $value;
                $this->validate([
                    'field' => $item,
                    'value' => $value,
                    'rules' => $this->makeRule($processedRules['rules'][$item])
                ]);
            }
        }
        return $this;
    }

    protected function processRules(array $rules = [])
    {
        //$fields = array_keys($rules);
        $fieldArray = [];
        $rulesArray = [];
        $labelsArrary = [];
        foreach ($rules as $field => $rules) {
            if (($pipe = strpos($field, '|')) !== false) {
                $labels = explode("|", $field);
                $fieldArray[] = $labels[0];
                $rulesArray[$labels[0]] = $rules;
                $labelsArrary[$labels[0]] = $labels[1];
            } else {
                $fieldArray[] = $field;
                $rulesArray[$field] = $rules;
                //$labelsArrary[$field] = $field;
            }
        }
        return [
            'fields' => $fieldArray,
            'rules' => $rulesArray,
            'labels' => $labelsArrary
        ];
    }

    public function getSession()
    {
        return $this->session;
    }

    public function getResponse()
    {
        return $this->response;
    }

    

    public function failed()
    {
        return $this->message->hasMessages();
    }

    public function hasErrors()
    {
        return $this->failed();
    }

    public function passed()
    {
        return !$this->failed();
    }

    public function errors()
    {
        return $this->message;
    }

    public function getValidated()
    {
        return $this->fields;
    }

    public function addRuleMessage($rule, $message)
    {
        $this->messages[$rule] = $message;
    }

    public function addFieldMessage($field, $rule, $message)
    {
        $this->fieldMessages[$field][$rule] = $message;
    }

    public function addRule($rule, Closure $callback)
    {
        $this->customRules[$rule] = $callback;
    }

    protected function getRuleToCall($rule)
    {
        if (isset($this->customRules[$rule])) {
            return $this->customRules[$rule];
        }

        if (in_array($rule, $this->rules) && method_exists($this, 'validate_'.$rule)) {
            return [$this, 'validate_'.$rule];
        } else {
            throw new Exception("Un defined Method [validate_".$rule."]");
        }
    }

    protected function makeValidator($rule)
    {
        $validators = [];
        foreach ($rule as $validate) {
            if (($colon = strpos($validate, ':')) !== false) {
                $validates = explode(':', $validate); 
                $validators[$validates[0]] = $this->parseRule($validate);
            } else {
                $validators[$validate] = $this->parseRule($validate);
            }
        }
        return $validators;
    }

    protected function makeRule($rule)
    {
        if (is_string($rule)) {
            $rule = (is_string($rule)) ? explode('|', $rule) : $rule;
            return $this->makeValidator($rule);
        } 
        return $rule;
    }

    protected function getCommaSeparatedValues($string)
    {
        $parameters = $string;

        if (strpos($parameters, ',') !== false) {
            $parameters = explode(',', $parameters);
        }

        return $parameters;
    }

    protected function parseRule($rule)
	{
		$parameters = true;
		if (($colon = strpos($rule, ':')) !== false) {
            $rules = explode(':', $rule);           
            $parameters = $this->getCommaSeparatedValues(substr($rule, $colon + 1));
        } else {
            $parameters = true;
        }
		return $parameters;
    }

    protected function validate(array $item)
    {
        $field = $item['field'];
        
        foreach ($item['rules'] as $rule => $satisfy) {

            if (!call_user_func_array($this->getRuleToCall($rule), [$field, $item['value'], $satisfy])) {
                $this->message->addMessage(
                    $this->message($field, $satisfy, $rule),
                    $field
                );

                $this->sessionMessage();
            }
        }
    }

    protected function sessionMessage()
    {
        if ($this->session->exists('file-message')) {
            $this->message->addMessage(
                $this->session->get('file-message'), 
                $this->session->get('yuga-file-field')
            );

            $this->session->deleteMany(['file-message', 'yuga-file-field']);
        }
    }

    protected function message($field, $satisfy, $rule)
    {
        $rules = $this->processRules($this->fieldRules);
        if (is_array($satisfy)) {
            $satisfy = implode(', ', $satisfy);
        }
        if (in_array($field, array_keys($rules['labels']))) {
            $label = $rules['labels'][$field];
        } else {
            if (strpos($field, '_') !== false)
                $label = ucwords(str_replace('_', ' ', $field));
            else
                $label = ucfirst($field);
        }
        
        if (is_array($this->items[$field])) {
            $message =  str_replace(['{field}', '{satisfy}', '{value}'], [$label, $satisfy, implode(', ', $this->items[$field])], $this->messagesArray[$rule]);
        } else {
            $message = str_replace(['{field}', '{satisfy}', '{value}'], [$label, $satisfy, $this->items[$field]], $this->messages[$rule]);
        }
        
        if (isset($this->fieldMessages[$field])) {
            if (strstr($message, $field) && isset($this->fieldMessages[$field][$rule])) {
                $message = $this->fieldMessages[$field][$rule];
            }
        }

        return $message;
    }

    public function validator($rules = [])
    {
        $this->check($this->request->getInput()->all(), $rules);
        if ($this->failed()) {
            if ($this->request->isAjax()) {
                return $this->errors();
            } else {
                $this->session->put('errors', $this->errors());
                $this->request->addOld();
                $this->response->redirect->back();
                
            }
            
        }
        $this->session->delete('old-data');
        
        if ($this->request->isAjax()) {
            return $this->errors();
        }
        return $this;
    }

    protected function validate_required($field, $values, $satisfy)
    {
        if ($satisfy == 'exists') {
            if ($this->request->exists($field)) {
                if (is_array($values)) {
                    $valid = true;
                    foreach ($values as $value) {
                        if (empty(trim($value)))
                            $valid = false;
                    }
                    return $valid;
                }
                return !empty(trim($values));
            }
            return;
        }
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if (empty(trim($value)))
                    $valid = false;
            }
            return $valid;
        }

        return !empty(trim($values));
    }

    protected function validate_max($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if ((mb_strlen($value) <= $satisfy) !== true)
                    $valid = false;
            }
            return $valid;
        }
        return mb_strlen($values) <= $satisfy;
    }

    protected function validate_min($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if ((mb_strlen($value) >= $satisfy) !== true)
                    $valid = false;
            }
            return $valid;
        }
        return mb_strlen($values) >= $satisfy;
    }

    protected function validate_email($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if (!filter_var($values, FILTER_VALIDATE_EMAIL))
                    $valid = false;
            }
            return $valid;
        }
        return filter_var($values, FILTER_VALIDATE_EMAIL);
    }

    protected function validate_alnum($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if (!ctype_alnum($value))
                    $valid = false;
            }
            return $valid;
        }
        return ctype_alnum($values);
    }

    protected function validate_string($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if (!is_string($value))
                    $valid = false;
            }
            return $valid;
        }
        return is_string($values);
    }

    protected function validate_int($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if (!(int)$value)
                    $valid = false;
            }
            return $valid;
        }
        return (int)$values;
    }

    protected function validate_matches($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if ($value !== $this->items[$satisfy])
                    $valid = false;
            }
            return $valid;
        }
        return $values === $this->items[$satisfy];
    }

    protected function validate_unique($field, $value, $satisfy)
    {
        $satisfies = explode('(', $satisfy);
        $satisfy = $satisfies[0];
        if (count($satisfies) > 1) {
            $field = str_replace(')', '', $satisfies[1]);
        }
        
        if (is_array($value))
            return !\DB::table($satisfy)->whereIn($field, $value)->first();
        else
            return !\DB::table($satisfy)->where($field, $value)->first();
    }

    protected function validate_in($field, $values, $satisfy)
    {
        if (is_array($values)) {
            $valid = true;
            foreach ($values as $value) {
                if (!in_array($value, $satisfy))
                    $valid = false;
            }
            return $valid;
        }
        return in_array($values, $satisfy);
    }

    protected function validate_file($field, $value, $satisfy)
    {
        return $this->request->hasFile($field) ? true : false;
    }

    protected function validate_confirmed($field, $value, $satisfy)
    {
        return $value === $this->items["{$field}_confirmation"];
    }
}