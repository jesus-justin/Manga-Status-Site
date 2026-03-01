<?php
/**
 * Pagination Helper
 * 
 * Provides pagination utilities for database queries
 */

class Pagination {
    private $currentPage;
    private $itemsPerPage;
    private $totalItems;
    private $totalPages;

    public function __construct($currentPage = 1, $itemsPerPage = 10) {
        $this->currentPage = max(1, (int)$currentPage);
        $this->itemsPerPage = max(1, (int)$itemsPerPage);
        $this->totalItems = 0;
        $this->totalPages = 0;
    }

    /**
     * Set total items
     */
    public function setTotal($total) {
        $this->totalItems = (int)$total;
        $this->totalPages = ceil($this->totalItems / $this->itemsPerPage);
        return $this;
    }

    /**
     * Get offset for SQL query
     */
    public function getOffset() {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }

    /**
     * Get limit for SQL query
     */
    public function getLimit() {
        return $this->itemsPerPage;
    }

    /**
     * Get current page
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }

    /**
     * Get total pages
     */
    public function getTotalPages() {
        return $this->totalPages;
    }

    /**
     * Check if has previous page
     */
    public function hasPreviousPage() {
        return $this->currentPage > 1;
    }

    /**
     * Check if has next page
     */
    public function hasNextPage() {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Get previous page number
     */
    public function getPreviousPage() {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : 1;
    }

    /**
     * Get next page number
     */
    public function getNextPage() {
        return $this->hasNextPage() ? $this->currentPage + 1 : $this->totalPages;
    }

    /**
     * Get pagination data
     */
    public function getPaginationData() {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->itemsPerPage,
            'total' => $this->totalItems,
            'total_pages' => $this->totalPages,
            'has_previous' => $this->hasPreviousPage(),
            'has_next' => $this->hasNextPage(),
            'previous_page' => $this->getPreviousPage(),
            'next_page' => $this->getNextPage()
        ];
    }

    /**
     * Generate page links
     */
    public function getPageLinks($currentUrl = '', $maxLinks = 5) {
        $links = [];
        $startPage = max(1, $this->currentPage - floor($maxLinks / 2));
        $endPage = min($this->totalPages, $startPage + $maxLinks - 1);
        $startPage = max(1, $endPage - $maxLinks + 1);

        for ($i = $startPage; $i <= $endPage; $i++) {
            $links[] = [
                'page' => $i,
                'is_current' => $i === $this->currentPage,
                'url' => $this->buildUrl($currentUrl, $i)
            ];
        }

        return $links;
    }

    /**
     * Build pagination URL
     */
    private function buildUrl($baseUrl, $page) {
        $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
        return $baseUrl . $separator . 'page=' . $page;
    }
}

?>
