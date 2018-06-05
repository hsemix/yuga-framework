<?php
namespace Yuga\Interfaces\Application;

use Yuga\Interfaces\Providers\IServiceProvider;

interface Application
{
    public function terminate();
    public function getLocale();
    public function getCharset();
    public function getTimezone();
    public function setLocale($local);
    public function getDefaultLocale();
    public function runningInConsole();
    public function getEncryptionMethod();
    public function setTimezone($timezone);
    public function setRequestForYugaConsole();
    public function setEncryptionMethod($method);
    public function setDefaultLocale($defaultLocale);
    public static function onRequest($method, $parameters = []);
    public function registerProvider(IServiceProvider $provider);
}