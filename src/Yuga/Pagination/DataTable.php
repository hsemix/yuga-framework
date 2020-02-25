<?php

namespace Yuga\Pagination;

use Iterator;
use Countable;
use ArrayAccess;
use JsonSerializable;
use Yuga\Http\Request;

class Datatable extends Paginator
{
    /**
     * The current path resolver callback.
     *
     * @var \Closure
     */
    protected static $currentPathResolver;
    /**
     * The query string variable used to store the page.
     *
     * @var string
     */
    protected $pageName = 'draw';
    /**
     * The path variable used to store the page.
     *
     * @var string
     */
    protected $path;
    /**
     * The per-page variable used to store the page.
     *
     * @var int
     */
    protected $perPage;
    /**
    * The current-page variable used tn the page.
    *
    * @var int
    */
    protected $currentPage;
    /**
     * The total items count variable 
     * 
     * @var int
     */
    protected $totalCount;

    /**
     * Create a new paginator instance.
     *
     * @param  mixed  $items
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($perPage, $currentPage = null, array $options = [])
    {
        foreach ($options as $key => $value) {
            $this->{$key} = $value;
        }

        $this->perPage = $perPage;
        $this->currentPage = $this->setCurrentPage($currentPage);
        $this->path = $this->path !== '/' ? rtrim($this->path, '/') : $this->path;
    }

    /**
     * Resolve the current page or return the default value.
     *
     * @param  string  $pageName
     * @param  int  $default
     * @return int
     */
    public static function resolveCurrentPage($pageName = 'draw', $default = 1)
    {
        $page = $default;
        $request = new Request;
        $url = explode('?', $request->getUri());
        if (count($url) > 1) {
            $page = (int) $request->get($pageName) ? : $page;
        }

        return $page;
    }

    /**
     * Resolve the current request path or return the default value.
     *
     * @param  string  $default
     * @return string
     */
    public static function resolveCurrentPath($default = '/')
    {
        if (isset(static::$currentPathResolver)) {
            return call_user_func(static::$currentPathResolver);
        }

        return $default;
    }

    /**
     * Get the current page for the request.
     *
     * @param  int  $currentPage
     * @return int
     */
    protected function setCurrentPage($currentPage)
    {
        $currentPage = $currentPage ?: static::resolveCurrentPage();

        return $this->isValidPageNumber($currentPage) ? (int) $currentPage : 1;
    }

    /**
     * Determine if the given value is a valid page number.
     *
     * @param  int  $page
     * @return bool
     */
    protected function isValidPageNumber($page)
    {
        return $page >= 1 && filter_var($page, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * Get the current uri or path
     * 
     * @param null
     * 
     * @return string
     */
    public function getCurrentPath()
    {
        return $this->path;
    }

    public function getRecordsFilteredCount()
    {
        return $this->recordsFiltered;
    }

    public function getTotalRecordsCount()
    {
        return $this->recordsTotal;
    }

    public function toArray()
    {
        return [
            'data'              => $this->items,
            'recordsTotal'      => $this->getTotalRecordsCount(),
            'recordsFiltered'   => $this->getRecordsFilteredCount(),
        ];
    }
}