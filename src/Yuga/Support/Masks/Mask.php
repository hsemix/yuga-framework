<?php
/**
 * Mask - Implements a Mask for the registered Services.
 *
 * @author <semix.hamidouh@gmail.com>
 *
 * @version 4.0.0
 */

namespace Yuga\Support\Masks;

abstract class Mask
{
    /**
     * The application instance being facaded.
     *
     * @var \Yuga\Application\Application
     */
    protected static $app;

    /**
     * The resolved object instances.
     *
     * @var array
     */
    protected static $resolvedInstance = [];

    /**
     * Get the registered name of the component.
     *
     * @throws \RuntimeException
     *
     * @return string
     */
    protected static function getMaskAccessor()
    {
        throw new \RuntimeException('Mask does not implement getMaskAccessor method.');
    }

    /**
     * Resolve the facade root instance from the container.
     *
     * @param string $name
     *
     * @return mixed
     */
    protected static function resolveMaskInstance($name)
    {
        if (is_object($name)) {
            return $name;
        }

        if (isset(static::$resolvedInstance[$name])) {
            return static::$resolvedInstance[$name];
        }

        return static::$resolvedInstance[$name] = static::$app[$name]; // ?? app()[$name];
    }

    /**
     * Set the application instance.
     *
     * @param \Yuga\Application\Application $app
     *
     * @return void
     */
    public static function setMaskApplication($app)
    {
        static::$app = $app;
    }

    /**
     * Get the application instance.
     *
     * @return \Yuga\Application\Application $app
     */
    public static function getMaskApplication()
    {
        return static::$app;
    }

    /**
     * Clear a resolved facade instance.
     *
     * @param string $name
     *
     * @return void
     */
    public static function clearResolvedInstance($name)
    {
        unset(static::$resolvedInstance[$name]);
    }

    /**
     * Clear all of the resolved instances.
     *
     * @return void
     */
    public static function clearResolvedInstances()
    {
        static::$resolvedInstance = [];
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $accessor = static::getMaskAccessor();

        $instance = static::resolveMaskInstance($accessor);

        return call_user_func_array([$instance, $method], $args);
    }
}
