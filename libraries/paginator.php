<?php
defined('SYS_PATH') or die('No direct script access.');

/**
 * Pagination links generator.
 *
 * @package    Ko
 * @author     Ko Team
 * @copyright  (c) 2008-2009 Ko Team
 * @version    $Id: paginator.php 109 2011-06-05 07:00:30Z eric $
 * @license    http://kophp.com/license.html
 */
class Paginator
{

    /**
     * Default item count per page
     *
     * @var int
     */
    protected static $_defaultItemCountPerPage = 10;

    /**
     * Current page items
     *
     * @var Traversable
     */
    protected $_currentItems = null;

    /**
     * Current page number (starting from 1)
     *
     * @var integer
     */
    protected $_currentPageNumber = 1;

    /**
     * Number of items per page
     *
     * @var integer
     */
    protected $_itemCountPerPage = null;

    /**
     * Number of pages
     *
     * @var integer
     */
    protected $_pageCount = null;

    /**
     * Number of local pages (i.e., the number of discrete page numbers
     * that will be displayed, including the current page number)
     *
     * @var integer
     */
    protected $_pageRange = 10;

    /**
     * Pages
     *
     * @var array
     */
    protected $_pages = null;

    /**
     * Item count
     *
     * @var integer
     */
    protected $_totalItemCount = 0;

    /**
     * Constructor.
     *
     * @param array $count Total item count
     */
    public function __construct ($totalItemCount)
    {
        $this->_totalItemCount = (integer) $totalItemCount;
    }

    /**
     * Factory.
     *
     * @param  mixed $count
     * @param  string $adapter
     * @param  array $prefixPaths
     * @return Paginator
     */
    public static function factory ($count)
    {
        return new self((int) $count);
    }

    /**
     * Get the default item count per page
     *
     * @return int
     */
    public static function getDefaultItemCountPerPage ()
    {
        return self::$_defaultItemCountPerPage;
    }

    /**
     * Returns the number of pages.
     *
     * @return integer
     */
    public function getPageCount ()
    {
        if (! $this->_pageCount) {
            $this->_pageCount = $this->_calculatePageCount();
        }
        return $this->_pageCount;
    }

    /**
     * Returns the total number of items available.
     *
     * @return integer
     */
    public function getTotalItemCount ()
    {
        return $this->_totalItemCount;
    }

    /**
     * Returns the current page number.
     *
     * @return integer
     */
    public function getCurrentPageNumber ()
    {
        return $this->normalizePageNumber($this->_currentPageNumber);
    }

    /**
     * Sets the current page number.
     *
     * @param  integer $pageNumber Page number
     * @return Paginator $this
     */
    public function setCurrentPageNumber ($pageNumber)
    {
        $this->_currentPageNumber = (integer) $pageNumber;
        $this->_currentItems = null;
        return $this;
    }

    /**
     * Returns the number of items per page.
     *
     * @return integer
     */
    public function getItemCountPerPage ()
    {
        if ($this->_itemCountPerPage === null) {
            $this->_itemCountPerPage = self::getDefaultItemCountPerPage();
        }
        return $this->_itemCountPerPage;
    }

    /**
     * Sets the number of items per page.
     *
     * @param  integer $itemCountPerPage
     * @return Paginator $this
     */
    public function setItemCountPerPage ($itemCountPerPage)
    {
        $this->_itemCountPerPage = (integer) $itemCountPerPage;
        if ($this->_itemCountPerPage == 0) {
            $this->_itemCountPerPage = 1;
        }
        $this->_pageCount = $this->_calculatePageCount();
        $this->_currentItems = null;
        return $this;
    }

    /**
     * Returns the page range (see property declaration above).
     *
     * @return integer
     */
    public function getPageRange ()
    {
        return $this->_pageRange;
    }

    /**
     * Sets the page range (see property declaration above).
     *
     * @param  integer $pageRange
     * @return Paginator $this
     */
    public function setPageRange ($pageRange)
    {
        $this->_pageRange = (integer) $pageRange;
        return $this;
    }

    /**
     * Returns the page collection.
     *
     * @return array
     */
    public function getPages ()
    {
        if ($this->_pages === null) {
            $this->_pages = $this->_createPages();
        }
        return $this->_pages;
    }

    /**
     * Returns a subset of pages within a given range.
     *
     * @param  integer $lowerBound Lower bound of the range
     * @param  integer $upperBound Upper bound of the range
     * @return array
     */
    public function getPagesInRange ($lowerBound, $upperBound)
    {
        $lowerBound = $this->normalizePageNumber($lowerBound);
        $upperBound = $this->normalizePageNumber($upperBound);
        $pages = array();
        for ($pageNumber = $lowerBound; $pageNumber <= $upperBound; $pageNumber ++) {
            $pages[$pageNumber] = $pageNumber;
        }
        return $pages;
    }

    /**
     * Brings the page number in range of the paginator.
     *
     * @param  integer $pageNumber
     * @return integer
     */
    public function normalizePageNumber ($pageNumber)
    {
        if ($pageNumber < 1) {
            $pageNumber = 1;
        }
        $pageCount = $this->getPageCount();
        if ($pageCount > 0 and $pageNumber > $pageCount) {
            $pageNumber = $pageCount;
        }
        return $pageNumber;
    }

    /**
     * Calculates the page count.
     *
     * @return integer
     */
    protected function _calculatePageCount ()
    {
        return (integer) ceil($this->getTotalItemCount() / $this->_itemCountPerPage);
    }

    /**
     * Creates the page collection.
     *
     * @param  string $scrollingStyle Scrolling style
     * @return stdClass
     */
    protected function _createPages ()
    {
        $pageCount = $this->getPageCount();
        $currentPageNumber = $this->getCurrentPageNumber();
        $pages = new stdClass();
        $pages->pageCount = $pageCount;
        $pages->itemCountPerPage = $this->getItemCountPerPage();
        $pages->first = 1;
        $pages->current = $currentPageNumber;
        $pages->last = $pageCount;
        // Previous and next
        if ($currentPageNumber - 1 > 0) {
            $pages->previous = $currentPageNumber - 1;
        }
        if ($currentPageNumber + 1 <= $pageCount) {
            $pages->next = $currentPageNumber + 1;
        }
        // Pages in range
        $pages->pagesInRange = $this->_pagesInRange();
        $pages->firstPageInRange = min($pages->pagesInRange);
        $pages->lastPageInRange = max($pages->pagesInRange);
        // Items in page
        $pages->itemCountPerPage = $this->getItemCountPerPage();
        $pages->totalItemCount = $this->getTotalItemCount();
        $pages->pageCount = $this->getPageCount();
        return $pages;
    }

    /**
     * Returns an array of "local" pages given a page number and range.
     *
     * @param  integer $pageRange (Optional) Page range
     * @return array
     */
    public function _pagesInRange ($pageRange = null)
    {
        if ($pageRange === null) {
            $pageRange = $this->getPageRange();
        }
        $pageNumber = $this->getCurrentPageNumber();
        $pageCount = $this->getPageCount();
        if ($pageRange > $pageCount) {
            $pageRange = $pageCount;
        }
        $delta = ceil($pageRange / 2);
        if ($pageNumber - $delta > $pageCount - $pageRange) {
            $lowerBound = $pageCount - $pageRange + 1;
            $upperBound = $pageCount;
        } else {
            if ($pageNumber - $delta < 0) {
                $delta = $pageNumber;
            }
            $offset = $pageNumber - $delta;
            $lowerBound = $offset + 1;
            $upperBound = $offset + $pageRange;
        }
        return $this->getPagesInRange($lowerBound, $upperBound);
    }
}
