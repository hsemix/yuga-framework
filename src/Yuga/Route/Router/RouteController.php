<?php
namespace Yuga\Route\Router;

use Yuga\Http\Request;
use Yuga\Route\Support\IControllerRoute;

class RouteController extends LoadableRoute implements IControllerRoute
{
    protected $defaultMethod = 'index';
    protected $method;
    protected $names = [];

    public function __construct($url, protected $controller)
    {
        $this->setUrl($url);
        $this->setName(trim(str_replace('/', '.', $url), '/'));
    }

    /**
     * Check if route has given name.
     *
     * @param string $name
     * @return bool
     */
    #[\Override]
    public function hasName($name)
    {
        if ($this->name === null) {
            return false;
        }

        /* Remove method/type */
        if (str_contains($name, '.')) {
            $method = substr($name, strrpos($name, '.') + 1);
            $newName = substr($name, 0, strrpos($name, '.'));

            if (in_array($method, $this->names, false) && strtolower($this->name) === strtolower($newName)) {
                return true;
            }
        }

        return parent::hasName($name);
    }

    /**
     * @param string|null $method
     * @param string|array|null $parameters
     * @param string|null $name
     * @return string
     */
    #[\Override]
    public function findUrl($method = null, $parameters = null, $name = null)
    {
        if (str_contains((string) $name, '.')) {
            $found = array_search(substr((string) $name, strrpos((string) $name, '.') + 1), $this->names, false);
            if ($found !== false) {
                $method = (string)$found;
            }
        }

        $url = '';
        $parameters = (array)$parameters;

        if ($method !== null) {

            /* Remove requestType from method-name, if it exists */
            foreach (static::$requestTypes as $requestType) {

                if (stripos($method, (string) $requestType) === 0) {
                    $method = substr($method, strlen((string) $requestType));
                    break;
                }
            }

            $method .= '/';
        }

        $group = $this->getGroup();

        if ($group !== null && count($group->getDomains()) > 0) {
            $url .= '//' . $group->getDomains()[0];
        }

        $url .= '/' . trim((string) $this->getUrl(), '/') . '/' . strtolower((string) $method) . implode('/', $parameters);

        return '/' . trim($url, '/') . '/';
    }

    public function matchRoute($url, Request $request)
    {
        /* Match global regular-expression for route */
        $regexMatch = $this->matchRegex($request, $url);

        if ($regexMatch === false || (stripos($url, $this->url) !== 0 && strtolower($url) !== strtolower($this->url))) {
            return false;
        }

        $strippedUrl = trim(str_ireplace($this->url, '/', $url), '/');
        $path = explode('/', $strippedUrl);

        if (count($path) > 0) {

            $method = (isset($path[0]) === false || trim($path[0]) === '') ? $this->defaultMethod : $path[0];
            $this->method = $request->getMethod() . ucfirst((string) $method);

            $this->parameters = array_slice($path, 1);

            // Set callback
            $this->setCallback($this->controller . '@' . $this->method);

            return true;
        }

        return false;
    }

    /**
     * Get controller class-name.
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Get controller class-name.
     *
     * @param string $controller
     * @return static
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Return active method
     *
     * @return string
     */
    #[\Override]
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set active method
     *
     * @param string $method
     * @return static
     */
    #[\Override]
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Merge with information from another route.
     *
     * @param bool $merge
     * @return static
     */
    #[\Override]
    public function setSettings(array $values, $merge = false)
    {
        if (isset($values['names'])) {
            $this->names = $values['names'];
        }

        parent::setSettings($values, $merge);

        return $this;
    }

}