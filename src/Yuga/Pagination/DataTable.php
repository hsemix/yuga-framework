<?php

namespace Yuga\Pagination;

use Iterator;
use Countable;
use ArrayAccess;
use JsonSerializable;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Views\Widgets\Html\Html;
use Yuga\Database\Elegant\Collection;

class DataTable extends Paginator
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

    protected $additionalColumns = [];

    protected $columns = [];

    protected $dom = null;
    protected $buttons = [];

    /**
     * Create a new paginator instance.
     *
     * @param  mixed  $items
     * @param  int  $perPage
     * @param  int|null  $currentPage
     * @param  array  $options (path, query, fragment, pageName)
     * @return void
     */
    public function __construct($perPage = 10, $currentPage = null, array $options = [])
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

    public function getColumns(): array
    {
        return $this->columns;
    }

    public static function of(Collection $collection)
    {
        $paginator = new self(10, 1, [
            'tt' => '2',
        ]);
        return $paginator;
    }

    public function addColumn(string $title, callable $callable)
    {
        $this->additionalColumns[$title] = $callable(1);
        return $this;
    }

    public function tableColumns(array $columns)
    {
        
        if ($this->isAssociative($columns)) {
            $this->columns = array_map(function($column) {
                if (!is_array($column))
                    return ['data' => $column];
                return $column;
            }, $columns);
        } else {
            $this->columns = $columns;
        }

        if ((new Request)->isAjax())
            return $this->toJsonData();
        return $this;
    }

    protected function isAssociative(array $array)
    {
        return count(array_filter(array_keys($array), 'is_string')) === 0;
    }

    public function dom(string $type)
    {
        $this->dom = $type;
        if ((new Request)->isAjax())
            return $this->toJsonData();

        return $this;
    }

    public function buttons(array $buttons = [])
    {
        $this->buttons = $buttons;

        if ((new Request)->isAjax())
            return $this->toJsonData();
        return $this;
    }

    public function scripts(bool $readyState = true)
    {
        $settings = [
            "columns" => $this->columns,
            "processing" => true,
            "serverSide" => true,
            "ajax" => $this->path,
            "buttons" => $this->buttons,
            "dom" => $this->dom,
        ];

        $js = 'var tables = $("#table_' . Str::deCamelize(class_base($this->first())) . '").DataTable(' . json_encode($settings) . ');';
        if ($readyState)
            $js = '$(function(){' . $js . '});';
        
        return '<script type="text/javascript">' . $js . '</script>';
    }

    public function toJsonData()
    {
        return response()->json([
            'data' => $this,
            "recordsTotal" => $this->getTotalRecordsCount(),
            "recordsFiltered" => $this->getRecordsFilteredCount(),
        ]);
    }

    public function table(array $settings = [], bool $showFooter = false)
    {
        $table = new Html('table');
        $table->id('table_' . Str::deCamelize(class_base($this->first())));
        if (isset($settings['class']))
            $table->addClass($settings['class']);
           
        $tableHead = new Html('thead');
        $tableFoot = new Html('tfoot');
        $tableBody = new Html('tbody');

        foreach ($this->columns as $column) {
            $th = (new Html('th'))->addInnerHtml(ucwords(str_replace('_', ' ', isset($column['name']) ? $column['name'] : $column['data'])));
            $tableHead->addInnerHtml($th);
            if ($showFooter)
                $tableFoot->addInnerHtml($th);
        }

        $table->addInnerHtml($tableHead);
        $table->addInnerHtml($tableBody);
        if ($showFooter)
            $table->addInnerHtml($tableFoot);

        // $this->toJsonData();
        return $table;
    }
    
}