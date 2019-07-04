<?php
namespace Yuga\Pagination;

use Yuga\Http\Request;
use Yuga\Views\Widgets\Html\Html;

class Pagination
{
	protected $currentPage;
	protected $perPage;
	protected $totalCount;
	protected $adjacents = 2;
	protected $pageName;
	
	public function __construct($page = 1, $perPage = 15, $totalCount = 0, $pageName = 'page')
	{
		$this->currentPage = (int)$page;
		$this->perPage = (int)$perPage;
		$this->totalCount = (int)$totalCount;
		$this->pageName = (string) $pageName;
	}

	public function setPageDotsGap($gap = 2)
	{
		$this->adjacents = $gap;
		return $this;
	}

	public function getPageDotsGap()
	{
		return $this->adjacents;
	}

	/**
	 * Set the total number of records to be returned per page
	 * 
	 * @param int $perPage
	 * 
	 * @return int
	 */
	public function setPerPage($perPage = 15)
	{
		$this->perPage = $perPage;
		return $this;
	}

	/**
	 * Set the total number of records in an array or collection
	 * 
	 * @param int|0 $total
	 * 
	 * @return int
	 */
	public function setTotalCount($total = 0)
	{
		$this->totalCount = $total;
		return $this;
	}

	/**
	 * Set the current page
	 * 
	 * @param int $page
	 * 
	 * @return int
	 */
	public function setCurrentPage($page = 1)
	{
		$this->currentPage = $page;
		return $this;
	}

	/**
	 * Get the current page
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	/**
	 * Get the number of records per page
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function getPerPage()
	{
		return $this->perPage;
	}

	/**
	 * Get the total records in a given array or collection
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function getTotalCount()
	{
		return $this->totalCount;
	}

	/**
	 * Calculate the offset
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function offset()
	{
		return ($this->currentPage - 1) * $this->perPage;
	}

	/**
	 * Get the total number of pages
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function totalPages()
	{
		return ceil($this->totalCount / $this->perPage);
	}

	/**
	 * Get the previous page
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function previousPage()
	{
		return $this->currentPage - 1;
	}

	/**
	 * Get the next page
	 * 
	 * @param null
	 * 
	 * @return int
	 */
	public function nextPage()
	{
		return $this->currentPage + 1;
	}

	/**
	 * Determine whether there can be a previous page
	 * 
	 * @param null
	 * 
	 * @return bool
	 */
	public function hasPreviousPage()
	{
		return $this->previousPage() >= 1 ? true : false;
	}

	/**
	 * Determine whether there can be a next page
	 * 
	 * @param null
	 * 
	 * @return bool
	 */
	public function hasNextPage()
	{
		return $this->nextPage() <= $this->totalPages() ? true : false;
	}

	/**
	 * Get PageName
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
	 * Print out all the pages according to the user's preference
	 * 
	 * @param null|array $options 
	 * @param array $controlButtons
	 * 
	 * @return string $paginate
	 */
	public function render(array $options = null, $controlButtons = [])
	{
		$url = explode('?', (new Request)->getUri(true));
		$pageUrl = $url[0].'?page=';
		
		$parentElement = null;
		$parent = 'ul';
		$parentClass = 'pagination';
		$child = 'li';
		if ($options) {
			if (array_key_exists('parent-element', $options)) {
				if (array_key_exists('name', $options['parent-element'])) {
					$parent = $options['parent-element']['name'];
				} 
				if (array_key_exists('classes', $options['parent-element'])) {
					$parentClass = $options['parent-element']['classes'];
				}
			}
			if (array_key_exists('url', $options)) {
				$pageUrl = $url[0].'?'.$options['url'].'=';
			}

			if (array_key_exists('inner-element', $options)) {
				$child = $options['inner-element'];
			}

			if (array_key_exists('route', $options)) {
				$pageUrl = $options['route'].'?page=';
				if (array_key_exists('url', $options)) {
					$pageUrl = $options['route'].'?'.$options['url'].'=';
				}
			}
		}

		$parentElement = (new Html($parent))->addClass($parentClass);
    	$lastPageLessOne = $this->totalPages() - 1;
    	if ($this->totalPages() > 1) {
      		if ($this->hasPreviousPage()) {
				$previousPageListItem = (new Html($child))->addClass('previous');
				$previousPageLink = (new Html('a'))->addAttribute('href', host('/' . $pageUrl.$this->previousPage(), false))->addInnerHtml('&laquo; Prev');
				$previousPageListItem->addInnerHtml($previousPageLink);
				$parentElement->addInnerHtml($previousPageListItem);
      		}
      		if ($this->totalPages() < 7 + ($this->adjacents * 2)) {   
				for ($counter = 1; $counter <= $this->totalPages(); $counter++) {
					$paginationListItem = new Html($child);
					$paginationPageLink = (new Html('a'))->addAttribute('href', host('/' . $pageUrl.$counter, false))->addInnerHtml($counter);
					if ($counter == $this->currentPage) {
						$paginationListItem->addClass('active');
					}
					$paginationListItem->addInnerHtml($paginationPageLink);
					$parentElement->addInnerHtml($paginationListItem);
				}
			} elseif($this->totalPages() > 5 + ($this->adjacents * 2)) {
				if($this->currentPage < 1 + ($this->adjacents * 2)) {
					for($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++) {
						$paginationListItem = new Html($child);
						$paginationPageLink = (new Html('a'))->addAttribute('href', host('/' . $pageUrl.$counter, false))->addInnerHtml($counter);
						if ($counter == $this->currentPage) {
							$paginationListItem->addClass('active');
						}
						$paginationListItem->addInnerHtml($paginationPageLink);
						$parentElement->addInnerHtml($paginationListItem);    
					}
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('span'))->addInnerHtml('...')));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addAttribute('href', host('/' . $pageUrl.$lastPageLessOne, false))->addInnerHtml($lastPageLessOne)));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addAttribute('href', host('/' . $pageUrl.$this->totalPages(), false))->addInnerHtml($this->totalPages())));
				} elseif($this->totalPages()  - ($this->adjacents * 2) > $this->currentPage && $this->currentPage > ($this->adjacents * 2)) {
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addInnerHtml('1')->addAttribute('href', host('/' . $pageUrl.'1', false))));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addInnerHtml('2')->addAttribute('href', host('/' . $pageUrl.'2', false))));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('span'))->addInnerHtml('...')));
					for($counter = $this->currentPage - $this->adjacents; $counter <= $this->currentPage + $this->adjacents; $counter++) {
						$paginationListItem = new Html($child);
						$paginationPageLink = (new Html('a'))->addAttribute('href', host('/' . $pageUrl.$counter, false))->addInnerHtml($counter);
						if ($counter == $this->currentPage) {
							$paginationListItem->addClass('active');
						}
						$paginationListItem->addInnerHtml($paginationPageLink);
						$parentElement->addInnerHtml($paginationListItem);      
					}
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('span'))->addInnerHtml('...')));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addAttribute('href', host('/' . $pageUrl.$lastPageLessOne, false))->addInnerHtml($lastPageLessOne)));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addAttribute('href', host('/' . $pageUrl.$this->totalPages(), false))->addInnerHtml($this->totalPages()))); 
				} else {
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addInnerHtml('1')->addAttribute('href', host('/' . $pageUrl.'1', false))));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('a'))->addInnerHtml('2')->addAttribute('href', host('/' . $pageUrl.'2', false))));
					$parentElement->addInnerHtml((new Html($child))->addInnerHtml((new Html('span'))->addInnerHtml('...')));
					for($counter = $this->totalPages() - (2 + ($this->adjacents * 2)); $counter <= $this->totalPages(); $counter++) {
						$paginationListItem = new Html($child);
						$paginationPageLink = (new Html('a'))->addAttribute('href', host('/' . $pageUrl.$counter, false))->addInnerHtml($counter);
						if ($counter == $this->currentPage) {
							$paginationListItem->addClass('active');
						}
						$paginationListItem->addInnerHtml($paginationPageLink);
						$parentElement->addInnerHtml($paginationListItem);    
					}
				}
      		} 
			if ($this->hasNextPage()) {
				$nextPageListItem = (new Html($child))->addClass('next');
				$nextPageLink = (new Html('a'))->addAttribute('href', host('/' . $pageUrl.$this->nextPage(), false))->addInnerHtml('Next &raquo;');
				$nextPageListItem->addInnerHtml($nextPageLink);
				$parentElement->addInnerHtml($nextPageListItem);
			}
		}
		return $parentElement;
	}

	/**
	 * Whether pages exist or not
	 * 
	 * @param null
	 * @return bool
	 */
	public function hasPages()
	{
		return $this->getTotalCount() > $this->getPerPage() ? true : false;
	}

	/**
	 * Get all pages inform of an array
	 * 
	 * @param null
	 * @return array
	 */
	public function getPagesArray()
	{
		$pageLinks = [];
		$lastPageLessOne = $this->totalPages() - 1;
		if ($this->totalPages() < 7 + ($this->adjacents * 2)) { 
			$pages = [];
			for ($counter = 1; $counter <= $this->totalPages(); $counter++) {
				$pages[] = $counter;
			}
			$pageLinks[] = $pages;
		} else if($this->totalPages() > 5 + ($this->adjacents * 2)) {
			if($this->currentPage < 1 + ($this->adjacents * 2)) {
				$pages = [];
				
				for($counter = 1; $counter < 4 + ($this->adjacents * 2); $counter++) {
					$pages[] = $counter;
				}
				$pageLinks[] = $pages;
				$pageLinks[] = '...';
				$pageLinks[] = [$lastPageLessOne, $this->totalPages()];
			}  else if($this->totalPages()  - ($this->adjacents * 2) > $this->currentPage && $this->currentPage > ($this->adjacents * 2)) {
				$before = [];
				for ($gaps = 1; $gaps <= $this->adjacents; $gaps++) {
					$before[] = $gaps;
				}
				$pageLinks[] = $before;
				$pageLinks[] = '...';
				$pages = [];
				for($counter = $this->currentPage - $this->adjacents; $counter <= $this->currentPage + $this->adjacents; $counter++) {
					$pages[] = $counter;    
				}
				$pageLinks[] = $pages;
				$pageLinks[] = '...';

				$after = [];
				for ($gaps = $this->totalPages(); $gaps >= $this->adjacents; $gaps--) {
					$after[] = $gaps;
					if (($this->totalPages() - $gaps) + 1 == $this->adjacents)
						break;
				}
				$pageLinks[] = array_reverse($after);
			} else {
				$before = [];
				for ($gaps = 1; $gaps <= $this->adjacents; $gaps++) {
					$before[] = $gaps;
				}
				$pageLinks[] = $before;
				$pageLinks[] = '...';
				$pages = [];
				for($counter = $this->totalPages() - (2 + ($this->adjacents * 2)); $counter <= $this->totalPages(); $counter++) {
					$pages[] = $counter;
				}
				$pageLinks[] = $pages;
			}
		}

		return $pageLinks;
	}
	
}