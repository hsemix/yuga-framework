<?php
/**
 * @author Mahad Tech Solutions
 */
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
        'string'
    ];

    public $messages = [
        'required' => '{field} field is required!',
        'min' => '{field} must be a minimum of {satisfy} characters',
        'max' => '{field} must be a maximum of {satisfy} characters',
        'email' => '{field} {value} is not a valid email address',
        'alnum' => '{field} must contain letters and numbers only',
        'matches' => '{field} must match {satisfy}',
        'unique' => '{field} already exists',
        'in' => '{field} must be in {satisfy}',
        'file' => '{field} must be an uploadable file',
        'int' => '{field} must be an number',
        'string' => '{field} must be a string'
    ];

    public $fieldMessages = [];
    public $customRules = [];
    protected $response;
    protected $session;
    protected $fields = [];
    public function __construct(Message $message, Response $response, Session $session, App $app, Request $request)
    {
        $this->app = $app;
        $this->message = $message;
        $this->response = $response;
        $this->session = $session;
        $this->request = $request;
    }

    public function check($items, $rules)
    {
        $this->items = $items;
        foreach ($items as $item => $value) {
            if (in_array($item, array_keys($rules))) {
                $this->fields[$item] = $value;
                $this->validate([
                    'field' => $item,
                    'value' => $value,
                    'rules' => $this->makeRule($rules[$item])
                ]);
            }
        }
        return $this;
        //return $this->fields;
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
        
        if (is_array($satisfy))
            $satisfy = implode(', ', $satisfy);
        if (strpos($field, '_') !== false)
            $label = ucwords(str_replace('_', ' ', $field));
        else
            $label = ucfirst($field);
        $message = str_replace(['{field}', '{satisfy}', '{value}'], [$label, $satisfy, $this->items[$field]], $this->messages[$rule]);
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

    protected function validate_required($field, $value, $satisfy)
    {
        return !empty(trim($value));
    }

    protected function validate_max($field, $value, $satisfy)
    {
        return mb_strlen($value) <= $satisfy;
    }

    protected function validate_min($field, $value, $satisfy)
    {
        return mb_strlen($value) >= $satisfy;
    }

    protected function validate_email($field, $value, $satisfy)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    protected function validate_alnum($field, $value, $satisfy)
    {
        return ctype_alnum($value);
    }

    protected function validate_string($field, $value, $satisfy)
    {
        return is_string($value);
    }

    protected function validate_int($field, $value, $satisfy)
    {
        return (int)$value;
    }

    protected function validate_matches($field, $value, $satisfy)
    {
        return $value === $this->items[$satisfy];
    }

    protected function validate_unique($field, $value, $satisfy)
    {
        return !\DB::table($satisfy)->where($field, $value)->first();
    }

    protected function validate_in($field, $value, $satisfy)
    {
        return in_array($value, $satisfy);
    }

    protected function validate_file($field, $value, $satisfy)
    {
        return $this->request->hasFile($field)?true : false;
    }
}