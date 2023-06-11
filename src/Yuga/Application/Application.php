<?php

namespace Yuga\Application;

use Yuga\Debug;
use Yuga\Boolean;
use Tracy\Debugger;
use Yuga\Providers\Composer\PackageManager;
use Yuga\Route\Route;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Http\Redirect;
use Yuga\Http\Response;
use Yuga\Support\Config;
use Yuga\View\Client\Jquery;
use Yuga\Container\Container;
use Yuga\Views\UI\Site as UI;
use App\Middleware\WebMiddleware;
use Yuga\Logger\LogServiceProvider;
use Yuga\Route\RouteServiceProvider;
use Yuga\Events\EventServiceProvider;
use Yuga\Http\Request as HttpRequest;
use Yuga\Invocation\CallableResolver;
use Yuga\Database\Tracy\DatabasePanel;
use Yuga\Providers\YugaServiceProvider;
use Yuga\Database\ElegantServiceProvider;
use Yuga\Providers\ClassAliasServiceProvider;
use Yuga\Interfaces\Providers\IServiceProvider;
use Yuga\Route\Exceptions\NotFoundHttpExceptionHandler;
use Yuga\Interfaces\Application\Application as IApplication;

class Application extends Container implements IApplication
{
    const VERSION = '4.3.7';
    const CHARSET_UTF8 = 'UTF-8';

     /**
     * Start the mvvm application by defaut
     * <code>$this->getSite()</code> from a ViewModel returns $this->site
     */
    public $site;
    /**
     * Store the configuration instance in this variable so we can use it as
     * <code>$this->app->config->get('db.default.settings')</code> from a controller
     *
     * @var \Yuga\Support\Config
     */
    public $config;

    /**
     * The base file path of the application so we can install the framework 
     * in a different directory and access it entiry
     *
     * @var string
     */
    protected $basePath;

    /**
     * The application instance is to be stored in this variable
     *
     * @var \Yuga\Application\Application
     */
    protected static $app;

    /**
     * The Default Application language we shall use
     * can be changed
     *
     * @var string
     */
    protected $locale = 'en';

    /**
     * The Application debug mode default is false 
     * can be changed in the .env file
     *
     * @var boolean
     */
    protected $debugEnabled = false;
    
    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];

    /**
     * The encryption method we shall use throught the entire application
     * can be changed later
     *
     * @var string
     */
    protected $encryptionMethod = 'AES-256-CBC';

    protected $debuggerStarted = false;

    protected $booted = false;

    protected $charset;

    /**
     * composer vendor directory
     */
    protected $vendorDir;

    /**
     * The prefixes of absolute cache paths for use during normalization.
     *
     * @var string[]
     */
    protected $absoluteCachePathPrefixes = ['/', '\\'];
    
    public function __construct($root = null)
    {
        $this->site = new UI;
        $this->basePath = $root;
        $this->charset = static::CHARSET_UTF8;
    }

    /**
     * Get the base path of the Laravel installation.
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->basePath.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the boot directory.
     *
     * @param  string  $path
     * @return string
     */
    public function bootPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'boot'.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the application configuration files.
     *
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config'.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        return $this->normalizeCachePath('APP_CONFIG_CACHE', 'cache/config.php');
    }

    /**
     * Get the path to the cached services.php file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return $this->normalizeCachePath('APP_SERVICES_CACHE', 'cache/services.php');
    }

    /**
     * Get the path to the cached packages.php file.
     *
     * @return string
     */
    public function getCachedPackagesPath()
    {
        return $this->normalizeCachePath('APP_PACKAGES_CACHE', 'cache/packages.php');
    }

    /**
     * Normalize a relative or absolute path to a cache file.
     *
     * @param  string  $key
     * @param  string  $default
     * @return string
     */
    protected function normalizeCachePath($key, $default)
    {
        if (is_null($env = env($key))) {
            return $this->bootPath($default);
        }

        return Str::startsWith($env, $this->absoluteCachePathPrefixes)
                ? $env
                : $this->basePath($env);
    }

    /**
     * Run the Yuga application
     */
    public function run()
    {
        $this->singleton('config', Config::class);
        $this->config = $this->get('config');
        // load default class alias here
        $this->setVendorDir($this->basePath.DIRECTORY_SEPARATOR.'vendor');
        if (!$this->runningInConsole()) {
            $this->setDebugEnabled(env('DEBUG_MODE', false)); 
            $this->initTracy();  
        }
        $this->registerConfig();
        if ($this->debuggerStarted) {
            $this['events']->dispatch('on:yuga-tracy');
        }
        $this->registerBaseBindings($this);
        $this->registerDefaultProviders();
        $this['events']->dispatch('on:app-start');
        
        if (!$this->runningInConsole()) {
            $this->make('session')->delete('errors');
        }

        
        return $this;
    }

    public function setVendorDir($vendorDir)
    {
        $this->vendorDir = $vendorDir;
        return $this;
    }

    public function getVendorDir()
    {
        return $this->vendorDir;
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

    /**
     * Register the Service providers
     *
     * @return void
     */
    protected function registerConfig()
    {
        if (!static::$app) {
            static::$app = $this;
        }
        $providers = $this->config->load('config.ServiceProviders');
        $this->registerConfigProviders();
        foreach ($this->config->getAll() as $name => $provider) {
            if (class_exists($provider)) {
                $this->singleton($name, $provider);
                $provider = $this->resolve($name);
                $this->registerProvider($provider);
            }
        }

        if (env('ROUTER_BOOTED', false)) {
            if (env('ENABLE_MVP_ROUTES', false)) {
                
                Route::group(['middleware' => 'web', 'namespace' => 'App\Controllers', 'exceptionHandler' => NotFoundHttpExceptionHandler::class], function () {
                    $routePrefix = '/' . trim(env('PREFIX_MVP_ROUTE', '/'), '/') . '/';
                    $routePrefix = ($routePrefix == '//') ? '/' : $routePrefix;
                    $controller = env('MVP_CONTROLLER', 'Controller');
                    if (env('MATCH_ROUTES_TO_CONTROLLERS', false)) {
                        trigger_error("MVP ROUTING and IMPLICIT ROUTING can not co-exist", E_USER_WARNING);
                    }

                    Route::csrfVerifier(new WebMiddleware);
                    Route::all($routePrefix . '{slug?}', $controller . '@show')->where(['slug' => '(.*)']);
                });
                
            }
        }
        
    }

    /**
     * Return a static instance of the Application instance through out the entire application
     * 
     * @param null
     * 
     * @return \Yuga\Application\Application
     */
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
        $this->bind('base_path', $this->basePath);
        $this->bind('Yuga\Interfaces\Application\Application', self::class);
        $this->bind('vendor_path', $this->vendorDir);
        $this->singleton(PackageManager::class, function () {
            return new PackageManager(
                $this->basePath, 
                env('COMPOSER_VENDOR_DIR', $this->vendorDir),
                path('config'.DIRECTORY_SEPARATOR.'ServiceProviders.php')
            );
        });
    }

    /**
     * Set the debug mode of the application i.e. it's either true or false
     * If it's set to true, it means the application needs to track all errors and display them in the browser.
     * Otherwise, errors are logged to a file
     * 
     * @param \boolean $bool
     * 
     * @return \Yuga\Appplication\Application $this
     */
    public function setDebugEnabled($bool)
    {
        $bool = Boolean::parse($bool);
        $this->debug = ($bool === true) ? new Debug() : null;
        $this->debugEnabled = $bool;

        return $this;
    }

    /**
     * Get the debug mode if set
     * 
     * @param null
     * 
     * @return bool
     */
    public function getDebugEnabled()
    {
        return $this->debugEnabled;
    }

    /**
     * Set the default application's encryption methode
     * 
     * @param string $method
     * 
     * @return \Yuga\Application\Application $this
     */
    public function setEncryptionMethod($method)
    {
        $this->encryptionMethod = $method;

        return $this;
    }

    /**
     * Get the Encryption method
     * 
     * @param null
     * 
     * @return string
     */
    public function getEncryptionMethod()
    {
        return $this->encryptionMethod;
    }

    /**
     * Register those providers that need to be loaded before any other providers
     * 
     * @return void
     */
    protected function registerConfigProviders()
    {
        $this->registerProvider(new EventServiceProvider($this));
    }

    /**
     * Register all of the base service providers.
     *
     * @return void
     */
    protected function registerDefaultProviders()
    {
        $this->registerProvider(new ElegantServiceProvider($this));

        $this->registerProvider(new LogServiceProvider($this));

        $this->registerProvider(new RouteServiceProvider($this));
        
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
     * @param  \Yuga\Http\Request  $request
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
    public static function onRequest($method, $parameters = [])
    {
        return forward_static_call_array([new Request, $method], $parameters);
    }

    /**
     * @param \Yuga\Interfaces\Providers\IServiceProvider $provider
     * 
     * @return \Yuga\Application\Application $this
     */
    public function registerProvider(IServiceProvider $provider)
    {
        if (!$this->providerLoaded($provider)) {
            if (method_exists($provider, 'register')) {
                $provider->register($this);
                $this->bootProvider($provider);
            }
            $this->loadedProviders[] = get_class($provider);
            return $this;
        }
    }

    public function getProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * @param \Yuga\Interfaces\Providers\IServiceProvider $provider
     * 
     * @return mixed
     */
    protected function bootProvider(IServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * Determine whether a service provider has been loaded or not
     * 
     * @param IServiceProvider $provider
     * 
     * @return bool
     */
    protected function providerLoaded(IServiceProvider $provider)
    {
        return array_key_exists(get_class($provider), $this->loadedProviders);
    }

    /**
     * Boot Miss Tracy for error debugging and dumping variables
     * 
     * @param null
     * 
     * @return \Yuga\Application\Application
     */
    protected function initTracy()
    {
        if ($this->getDebugEnabled() === true) {
            Debugger::enable(Debugger::DEVELOPMENT);
            $this->debuggerStarted = true;
        } else {
            $logDir = storage('logs');
            if(!is_dir($logDir)) {
                mkdir($logDir);
            }
            Debugger::enable(Debugger::PRODUCTION, $logDir);
            set_error_handler([new LogServiceProvider($this), 'logErrorToFile'], E_ALL);
        }    
        return $this;
    }

    /**
     * Get the character set
     * 
     * @param null
     * 
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }
    
    /**
     * Get the Application's Locale setting
     * 
     * @param null
     * 
     * @return string
     */
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
     * Set Application Timezone
     * 
     * @param \string $timezone
     * 
     * @return \Yuga\Application $this
     */
    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
        date_default_timezone_set($timezone);
        return $this;
    }

    /**
     * Set Application locale (language)
     * 
     * @param \string $timezone
     * @return \Yuga\Application $this
     */
    public function setLocale($locale)
    {
        $this->locale = strtolower($locale);
        setlocale(LC_ALL, $locale);
        return $this;
    }

    /**
     * Get the default Application locale (language=en)
     * 
     * @param null
     * 
     * @return string
     */
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