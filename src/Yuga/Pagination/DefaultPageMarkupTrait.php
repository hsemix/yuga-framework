<?php

namespace Yuga\Pagination;

use Yuga\Views\Widgets\Html\Html;

trait DefaultPageMarkupTrait
{
    public function getPreviousButton($text = '&laquo; Prev')
    {
        $previousPageListItem = (new Html('li'))->addClass('previous');
        if ($this->hasPreviousPage()) {
            $pageUrl = $this->getcurrentPath().'?'.$this->pagination->getPageName().'=';
            $previousPageLink = (new Html('a'))->addAttribute('href', host('/'.$pageUrl.$this->previousPage(), false))->addInnerHtml($text);
            $previousPageListItem->addInnerHtml($previousPageLink);
        } else {
            $previousPageListItem->addInnerHtml((new Html('span'))->addInnerHtml($text))->attr('aria-disabled', 'true');
        }

        return $previousPageListItem;
    }

    public function getPageLinks()
    {
        $pages = $this->getPagesArray();
        $pagination = '';
        $pageUrl = $this->getcurrentPath().'?'.$this->pagination->getPageName().'=';
        foreach ($pages as $page) {
            if (is_string($page)) {
                $pagination .= (new Html('li'))->addInnerHtml((new Html('span'))->addInnerHtml('...'));
            } else {
                foreach ($page as $link) {
                    $paginationListItem = new Html('li');
                    $paginationListItem = $paginationListItem->addInnerHtml((new Html('a'))->addAttribute('href', host('/'.$pageUrl.$link, false))->addInnerHtml($link));
                    if ($link == $this->getCurrentPage()) {
                        $paginationListItem->addClass('active');
                    }
                    $pagination .= $paginationListItem;
                }
            }
        }

        return $pagination;
    }

    public function getNextButton($text = 'Next &raquo;')
    {
        $nextPageListItem = (new Html('li'))->addClass('next');
        if ($this->hasNextPage()) {
            $pageUrl = $this->getcurrentPath().'?'.$this->pagination->getPageName().'=';
            $nextPageLink = (new Html('a'))->addAttribute('href', host('/'.$pageUrl.$this->nextPage(), false))->addInnerHtml($text);
            $nextPageListItem->addInnerHtml($nextPageLink);
        } else {
            $nextPageListItem->addInnerHtml((new Html('span'))->addInnerHtml($text))->attr('aria-disabled', 'true');
        }

        return $nextPageListItem;
    }
}
