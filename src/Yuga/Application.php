<?php
namespace Yuga;

use Yuga\Http\Request;
use Yuga\Http\Redirect;
use Yuga\Http\Response;
use Yuga\Support\Config;
use Whoops\Run as WhoopsRun;
use Yuga\Container\Container;
use Yuga\Views\UI\Site as UI;
use Yuga\Support\IServiceProvider;
use Yuga\Logger\LogServiceProvider;
use Yuga\Route\RouteServiceProvider;
use Yuga\Events\EventServiceProvider;
use Yuga\Http\Request as HttpRequest;
use Yuga\Invocation\CallableResolver;
use Yuga\Providers\YugaServiceProvider;
use Yuga\Database\ElegantServiceProvider;
use Yuga\Providers\ClassAliasServiceProvider;
use Whoops\Handler\PrettyPageHandler as PrettyPage;

class Application extends Container
{
    const CHARSET_UTF8 = 'UTF-8';
    const VERSION = '3.0.0';
    protected $basePath;
    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];
    protected static $app;
    public $config;
    public $site;
    protected $locale = 'en';
    protected $debugEnabled = false;
    protected $encryptionMethod = 'AES-256-CBC';

    protected $cssWrapRouteName = 'css/wrap';
    protected $jsWrapRouteName = 'js/wrap';
    protected $cssWrapRouteUrl = '/css-wrap';
    protected $jsWrapRouteUrl = '/js-wrap';

    public function __construct($root = null)
    {
        $this->site = new UI;
        $this->basePath = $root;
        $this->charset = static::CHARSET_UTF8;
        $this->singleton('config', Config::class);
        $this->config = $this->resolve('config');
        // load default class alias here
        
        if (!$this->runningInConsole()) {
            $this->setDebugEnabled(env('DEBUG_MODE', true)); 
            if ($this->getDebugEnabled()) 
                $this->initWhoops();
            else
                error_reporting(0);
        }
        $this->registerConfig();
        $this->registerBaseBindings($this);
        $this->registerDefaultProviders();
        if (!$this->runningInConsole()) {
            $this->make('session')->delete('errors');
        }
    }

    /**
     * Determine if we are running in the console.
     *
     * @return bool
     */
    public function runningInConsole()
    {
        return php_sapi_name() == 'cli';
    }

    protected function registerConfig()
    {
        if (!static::$app) {
            static::$app = $this;
        }
        $providers = $this->config->load('config.ServiceProviders');
        
        foreach ($this->config->getAll() as $name => $provider) {
            $this->singleton($name, $provider);
            $provider = $this->resolve($name);
            $this->registerProvider($provider);
        }
    }

    public static function getInstance()
    {
        return static::$app;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings($container)
    {
        $this->singleton('app', $this);
        $this->singleton(Container::class, $this);
    }

    /**
     * Set the debug mode of the application i.e. it's either true or false
     * If it's set to true, it means the application needs to track all errors and display them in the browser.
     * Otherwise, errors are logged to a file
     * 
     * @param \boolean $bool
     * 
     * @return \Yuga\Application $this
     */

    public function setDebugEnabled($bool)
    {
        $bool = Boolean::parse($bool);
        $this->debug = ($bool === true) ? new Debug() : null;
        $this->debugEnabled = $bool;

        return $this;
    }

    public function getDebugEnabled()
    {
        return $this->debugEnabled;
    }

    public function setEncryptionMethod($method)
    {
        $this->encryptionMethod = $method;

        return $this;
    }

    public function getEncryptionMethod()
    {
        return $this->encryptionMethod;
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerDefaultProviders()
    {
        $this->registerProvider(new EventServiceProvider($this));

        $this->registerProvider(new LogServiceProvider($this));

        $this->registerProvider(new RouteServiceProvider($this));

        $this->registerProvider(new ElegantServiceProvider($this));
        
        if ($this->runningInConsole()) {
            $this->registerProvider(new YugaServiceProvider($this));
        }
    }

    /**
     * Set the application request for the console environment.
     *
     * @return void
     */
    public function setRequestForYugaConsole()
    {
        $url = $this['config']->get('app.url', 'http://localhost');

        $parameters = [$url, 'GET', [], [], [], $_SERVER];

        $this->refreshRequest(static::onRequest('create', $parameters));
    }

    /**
     * Refresh the bound request instance in the container.
     *
     * @param  \Nova\Http\Request  $request
     * @return void
     */
    protected function refreshRequest(Request $request)
    {
        $this->singleton('request', $request);
    }

    /**
     * Call a method on the default request class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function onRequest($method, $parameters = array())
    {
        return forward_static_call_array([new Request, $method], $parameters);
    }

    /**
    * @param \Yuga\Support\IServiceProvider $provider
    * @return \Yuga\Application $this
    */

    public function registerProvider(IServiceProvider $provider)
    {
        if (!$this->providerLoaded($provider)) {
            if (method_exists($provider, 'register')) {
                $provider->register($this);
            }
            $this->loadedProviders[] = get_class($provider);
            return $this;
        }
    }

    protected function providerLoaded(IServiceProvider $provider)
    {
        return array_key_exists(get_class($provider), $this->loadedProviders);
    }

    /**
    * start whoops
    * @return \Yuga\Application
    */
    protected function initWhoops()
    {
        (new WhoopsRun)->pushHandler(new PrettyPage)->register();

        return $this;
    }

    public function getCharset()
    {
        return $this->charset;
    }
    
    public function getLocale()
    {
        return $this->locale;
    }

    /**
    * @return string $timezone
    */
    public function getTimezone()
    {
        return $this->timezone;
    }

    /**
    * @param \string $timezone
    */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        date_default_timezone_set($timezone);
    }

    public function setLocale($locale)
    {
        $this->locale = strtolower($locale);
        setlocale(LC_ALL, $locale);
    }


    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * Set site locale
     *
     * @param string $defaultLocale
     * @return static $this
     */
    public function setDefaultLocale($defaultLocale)
    {
        $this->defaultLocale = $defaultLocale;

        return $this;
    }

    /**
     * Shutdown the application
     */
    public function terminate()
    {
        exit(0);
    }
}