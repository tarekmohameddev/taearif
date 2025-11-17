<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Shared\Repositories\BaseRepositoryInterface;

/**
 * Invoice Repository Interface
 *
 * Contract for Invoice data access operations
 */
interface InvoiceRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Find invoice by number
     *
     * @param string $number
     * @return Invoice|null
     */
    public function findByNumber(string $number): ?Invoice;

    /**
     * Get revenue for period
     *
     * @param string $from
     * @param string $to
     * @return float
     */
    public function getRevenue(string $from, string $to): float;

    /**
     * Get paid invoices count
     *
     * @return int
     */
    public function getPaidCount(): int;

    /**
     * Get pending invoices count
     *
     * @return int
     */
    public function getPendingCount(): int;

    /**
     * Get rejected invoices count
     *
     * @return int
     */
    public function getRejectedCount(): int;

    /**
     * Get invoices with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getInvoices(array $filters = [], int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get revenue statistics summary.
     *
     * @return array
     */
    public function getRevenueStatistics(): array;

    /**
     * Get invoice statistics summary.
     *
     * @return array
     */
    public function getStatistics(): array;

    /**
     * Find active invoice for user.
     *
     * @param int $userId
     * @return Invoice|null
     */
    public function findActiveInvoiceForUser(int $userId): ?Invoice;

    /**
     * Find previous active invoice for user.
     *
     * @param int $userId
     * @return Invoice|null
     */
    public function findPreviousActiveInvoiceForUser(int $userId): ?Invoice;

    /**
     * Find latest invoice for user.
     *
     * @param int $userId
     * @return Invoice|null
     */
    public function findLatestForUser(int $userId): ?Invoice;

    /**
     * Get user invoice count.
     *
     * @param int $userId
     * @return int
     */
    public function getUserInvoiceCount(int $userId): int;

    /**
     * Update invoice status.
     *
     * @param int $id
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $id, int $status): bool;

    /**
     * Expire invoice by setting expire date.
     *
     * @param int $id
     * @param string $expireDate
     * @return bool
     */
    public function expireInvoice(int $id, string $expireDate): bool;
}

