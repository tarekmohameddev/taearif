<?php

namespace App\Domain\Billing\Repositories;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Invoice Repository
 *
 * Handles data access for Invoice model (memberships table)
 */
class InvoiceRepository extends BaseRepository implements InvoiceRepositoryInterface
{
    /**
     * InvoiceRepository constructor.
     *
     * @param Invoice $model
     */
    public function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    /**
     * Find invoice by number/transaction ID
     *
     * @param string $number
     * @return Invoice|null
     */
    public function findByNumber(string $number): ?Invoice
    {
        return $this->model->where('transaction_id', $number)
            ->with(['user', 'package'])
            ->first();
    }

    /**
     * Get revenue for period
     *
     * @param string $from
     * @param string $to
     * @return float
     */
    public function getRevenue(string $from, string $to): float
    {
        return $this->model
            ->paid()
            ->whereBetween('created_at', [$from, $to])
            ->sum('price');
    }

    /**
     * Get paid invoices count
     *
     * @return int
     */
    public function getPaidCount(): int
    {
        return $this->model->paid()->count();
    }

    /**
     * Get pending invoices count
     *
     * @return int
     */
    public function getPendingCount(): int
    {
        return $this->model->pending()->count();
    }

    /**
     * Get rejected invoices count
     *
     * @return int
     */
    public function getRejectedCount(): int
    {
        return $this->model->rejected()->count();
    }

    /**
     * Get all invoices with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInvoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'package']);

        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'paid') {
                $query->paid();
            } elseif ($filters['status'] === 'pending') {
                $query->pending();
            } elseif ($filters['status'] === 'rejected') {
                $query->rejected();
            }
        }

        // Payment method filter
        if (isset($filters['payment_method'])) {
            $query->byPaymentMethod($filters['payment_method']);
        }

        // User filter
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Package filter
        if (isset($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        // Trial filter
        if (isset($filters['is_trial'])) {
            if ($filters['is_trial']) {
                $query->trial();
            } else {
                $query->where('is_trial', false);
            }
        }

        // Date range filter
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->dateRange($filters['start_date'], $filters['end_date']);
        }

        // Search by transaction ID or user email
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function($userQuery) use ($search) {
                      $userQuery->where('email', 'like', "%{$search}%")
                               ->orWhere('username', 'like', "%{$search}%")
                               ->orWhere('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get pending invoices
     *
     * @return Collection
     */
    public function getPendingInvoices(): Collection
    {
        return $this->model->pending()
            ->with(['user', 'package'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent invoices
     *
     * @param int $days
     * @param int $limit
     * @return Collection
     */
    public function getRecentInvoices(int $days = 30, int $limit = 10): Collection
    {
        return $this->model->recent($days)
            ->with(['user', 'package'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get revenue statistics
     *
     * @return array
     */
    public function getRevenueStatistics(): array
    {
        $today = now()->toDateString();
        $thisMonth = now()->startOfMonth()->toDateString();
        $lastMonth = now()->subMonth()->startOfMonth()->toDateString();
        $lastMonthEnd = now()->subMonth()->endOfMonth()->toDateString();

        return [
            'total' => $this->model->paid()->sum('price'),
            'today' => $this->model->paid()
                ->whereDate('created_at', $today)
                ->sum('price'),
            'this_month' => $this->model->paid()
                ->whereDate('created_at', '>=', $thisMonth)
                ->sum('price'),
            'last_month' => $this->model->paid()
                ->whereBetween('created_at', [$lastMonth, $lastMonthEnd])
                ->sum('price'),
        ];
    }

    /**
     * Get invoice statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'paid' => $this->getPaidCount(),
            'pending' => $this->getPendingCount(),
            'rejected' => $this->getRejectedCount(),
            'trial' => $this->model->trial()->count(),
        ];
    }

    /**
     * Find active invoice for user
     *
     * @param int $userId
     * @return Invoice|null
     */
    public function findActiveInvoiceForUser(int $userId): ?Invoice
    {
        return $this->model->where('user_id', $userId)
            ->paid()
            ->where('start_date', '<=', now()->toDateString())
            ->where('expire_date', '>=', now()->toDateString())
            ->with(['package'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Find previous active invoice for user
     *
     * @param int $userId
     * @return Invoice|null
     */
    public function findPreviousActiveInvoiceForUser(int $userId): ?Invoice
    {
        return $this->model->where('user_id', $userId)
            ->paid()
            ->where('start_date', '<=', now()->toDateString())
            ->where('expire_date', '>=', now()->toDateString())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Get user invoice count
     *
     * @param int $userId
     * @return int
     */
    public function getUserInvoiceCount(int $userId): int
    {
        return $this->model->where('user_id', $userId)->count();
    }

    /**
     * Update invoice status
     *
     * @param int $id
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $id, int $status): bool
    {
        return $this->model->where('id', $id)->update(['status' => $status]);
    }

    /**
     * Expire invoice
     *
     * @param int $id
     * @param string $expireDate
     * @return bool
     */
    public function expireInvoice(int $id, string $expireDate): bool
    {
        return $this->model->where('id', $id)->update([
            'expire_date' => $expireDate
        ]);
    }
}

