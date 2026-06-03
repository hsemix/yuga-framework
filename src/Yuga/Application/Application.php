<?php

namespace Yuga\Application;

use App\Middleware\WebMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tracy\Debugger;
use Yuga\Boolean;
use Yuga\Container\Container;
use Yuga\Database\ElegantServiceProvider;
use Yuga\Debug;
use Yuga\Events\EventServiceProvider;
use Yuga\Http\Psr7\YugaRequestFactory;
use Yuga\Http\Psr7\YugaResponseFactory;
use Yuga\Http\Request;
use Yuga\Interfaces\Application\Application as IApplication;
use Yuga\Interfaces\Providers\IServiceProvider;
use Yuga\Logger\LogServiceProvider;
use Yuga\Providers\Composer\PackageManager;
use Yuga\Providers\YugaServiceProvider;
use Yuga\Route\Exceptions\NotFoundHttpExceptionHandler;
use Yuga\Route\Route;
use Yuga\Route\RouteServiceProvider;
use Yuga\Runtime\Kernel;
use Yuga\Session\SessionServiceProvider;
use Yuga\Support\Config;
use Yuga\Support\Str;
use Yuga\Views\UI\Site as UI;

class Application extends Container implements IApplication, Kernel
{
    const VERSION = '5.0.0';
    const CHARSET_UTF8 = 'UTF-8';

     /**
     * Start the mvvm application by defaut
     * <code>$this->getSite()</code> from a ViewModel returns $this->site
     * @var \Yuga\Views\UI\Site
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

    protected $defaultLocale;

    protected $debug;

    protected $timezone;

    /**
     * The prefixes of absolute cache paths for use during normalization.
     *
     * @var string[]
     */
    protected $absoluteCachePathPrefixes = ['/', '\\'];
    
    /**
     * @param string $root
     */
    public function __construct(
    /**
     * The base file path of the application so we can install the framework
     * in a different directory and access it entiry
     */
    protected $basePath = null)
    {
        $this->site = new UI;
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

        $this->registerConfigProviders();
        
        foreach ($this->config->load('config.ServiceProviders')->getAll() as $name => $provider) {
            if (class_exists($provider)) {
                $this->singleton($name, $provider);
                $provider = $this->resolve($name);
                $this->registerProvider($provider);
            }
        }
        
        if (env('ROUTER_BOOTED', false) && env('ENABLE_MVP_ROUTES', false)) {
            Route::group(['middleware' => 'web', 'namespace' => 'App\Controllers', 'exceptionHandler' => NotFoundHttpExceptionHandler::class], function (): void {
                $routePrefix = '/' . trim((string) env('PREFIX_MVP_ROUTE', '/'), '/') . '/';
                $routePrefix = ($routePrefix === '//') ? '/' : $routePrefix;
                $controller = env('MVP_CONTROLLER', 'Controller');
                if (env('MATCH_ROUTES_TO_CONTROLLERS', false)) {
                    trigger_error("MVP ROUTING and IMPLICIT ROUTING can not co-exist", E_USER_WARNING);
                }

                Route::csrfVerifier(new WebMiddleware);
                Route::all($routePrefix . '{slug?}', $controller . '@show')->where(['slug' => '(.*)']);
            });
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
        $this->bind(\Yuga\Interfaces\Application\Application::class, self::class);
        $this->bind('vendor_path', $this->vendorDir);
        $this->singleton(PackageManager::class, fn() => new PackageManager(
            $this->basePath, 
            env('COMPOSER_VENDOR_DIR', $this->vendorDir),
            path('config'.DIRECTORY_SEPARATOR.'ServiceProviders.php')
        ));
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
        $this->registerProvider(new SessionServiceProvider($this));
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
     * @return \Yuga\Application\Application $this
     */
    public function registerProvider(IServiceProvider $provider)
    {
        if (!$this->providerLoaded($provider)) {
            if (method_exists($provider, 'register')) {
                $provider->register($this);
                $this->bootProvider($provider);
                $this->prodviderScheduler($provider);
            }
            $this->loadedProviders[] = $provider::class;
            return $this;
        }
        return null;        
    }

    public function getProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * @return mixed
     */
    protected function bootProvider(IServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    protected function prodviderScheduler(IServiceProvider $provider)
    {
        if ($this->make('scheduler') && method_exists($provider, 'scheduler')) {
            return call_user_func([$provider, 'scheduler'], $this->make('scheduler'));
        }
    }

    /**
     * Determine whether a service provider has been loaded or not
     *
     *
     * @return bool
     */
    protected function providerLoaded(IServiceProvider $provider)
    {
        return array_key_exists($provider::class, $this->loadedProviders);
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
            Debugger::enable(Debugger::Development);
            $this->debuggerStarted = true;
        } else {
            $logDir = storage('logs');
            if(!is_dir($logDir)) {
                mkdir($logDir);
            }
            Debugger::enable(Debugger::Production, $logDir);
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
        $this->locale = strtolower((string) $locale);
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
    // public function terminate()
    // {
    //     exit(0);
    // }



    public function bootstrap()
    {
        if ($this->booted) {
            return $this;
        }

        $this->run();

        $router = \Yuga\Route\Route::router();

        if (method_exists($router, 'ensureRoutesLoaded')) {
            $router->ensureRoutesLoaded();
        }

        $this->booted = true;

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->bootstrap();

        $yugaRequest = YugaRequestFactory::fromPsr7($request);

        $router = \Yuga\Route\Route::router();

        // if (method_exists($router, 'setRequest')) {
        //     $router->setRequest($yugaRequest);
        // }

        if (method_exists($router, 'prepareForRequest')) {
            $router->prepareForRequest($yugaRequest);
        }

        ob_start(); 

        try {
            $result = $router->routeRequest();

            $buffer = ob_get_clean();

            // return YugaResponseFactory::fromMixed($result ?? $buffer);

            $response = YugaResponseFactory::fromMixed($result ?? $buffer);

            if (class_exists(\Yuga\Cookie\Cookie::class)) {
                $response = \Yuga\Cookie\Cookie::attachToResponse($response);
            }

            return $response;

        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // throw $e;
            return $this->renderThrowable($e);
        }
    }

    protected function renderThrowable(\Throwable $e): \Psr\Http\Message\ResponseInterface
    {
        // if ($this->debuggerStarted == true && class_exists(\Tracy\Debugger::class)) {
            ob_start();

            Debugger::getBlueScreen()->render($e);

            return new \Nyholm\Psr7\Response(
                500,
                ['Content-Type' => 'text/html; charset=UTF-8'],
                ob_get_clean()
            );
        // }

        // throw $e;
    }

    public function terminateRequest(ServerRequestInterface $request, ResponseInterface $response): void
    {
        if (method_exists($this, 'flushScopedInstances')) {
            $this->flushScopedInstances();
        }

        gc_collect_cycles();
    }
    
    /**
     * Shutdown the application
     */
    public function terminate(?ServerRequestInterface $request = null, ?ResponseInterface $response = null): void
    {
        if ($request && $response) {
            $this->terminateRequest($request, $response);
            return;
        }
    }
}
