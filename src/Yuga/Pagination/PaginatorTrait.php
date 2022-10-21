<?php

namespace Yuga\Pagination;

use Yuga\Http\Request;

trait PaginatorTrait
{
    // use DefaultPageMarkup;
    /**
     * Set Pagination for later.
     *
     * @param Pagination $pagination
     *
     * @return mixed
     */
    public function setPagination(Pagination $pagination)
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * Set the items for the paginator.
     *
     * @param mixed $items
     *
     * @return void
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * Print all pages.
     */
    public function pages(array $options = null)
    {
        return $this->pagination->render();
    }

    /**
     * Get the current page.
     *
     * @param null
     *
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->pagination->getCurrentPage();
    }

    /**
     * Get the number of records per page.
     *
     * @param null
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->pagination->getPerPage();
    }

    /**
     * Get the total records in a given array or collection.
     *
     * @param null
     *
     * @return int
     */
    public function getTotalCount()
    {
        return $this->pagination->getTotalCount();
    }

    /**
     * Get the total number of pages.
     *
     * @param null
     *
     * @return int
     */
    public function getTotalPages()
    {
        return $this->pagination->totalPages();
    }

    /**
     * Get the previous page.
     *
     * @param null
     *
     * @return int
     */
    public function previousPage()
    {
        return $this->pagination->previousPage();
    }

    /**
     * Get the next page.
     *
     * @param null
     *
     * @return int
     */
    public function nextPage()
    {
        return $this->pagination->nextPage();
    }

    /**
     * Determine whether there can be a previous page.
     *
     * @param null
     *
     * @return bool
     */
    public function hasPreviousPage()
    {
        return $this->pagination->hasPreviousPage();
    }

    /**
     * Determine whether there can be a next page.
     *
     * @param null
     *
     * @return bool
     */
    public function hasNextPage()
    {
        return $this->pagination->hasNextPage();
    }

    /**
     * Get PageName.
     *
     * @param null
     *
     * @return string
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * Get route.
     *
     * @param null
     *
     * @return string
     */
    public function getRoute()
    {
        $url = explode('?', (new Request())->getUri());
    }

    /**
     * Get the total number of pages shown before the dots appear.
     *
     * @param null
     *
     * @return int
     */
    public function getPageDotsGap()
    {
        return $this->pagination->getPageDotsGap();
    }

    /**
     * Get all pages in form of an array.
     *
     * @param null
     *
     * @return array
     */
    public function getPagesArray()
    {
        return $this->pagination->getPagesArray();
    }

    /**
     * Set the page count before dots appear.
     */
    public function setPageCountBeforeDots($pages)
    {
        $this->pagination->setPageDotsGap($pages);

        return $this;
    }
}
