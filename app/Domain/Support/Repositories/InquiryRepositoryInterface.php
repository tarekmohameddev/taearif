<?php

namespace App\Domain\Support\Repositories;

use App\Domain\Support\Models\Inquiry;
use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Inquiry Repository Interface
 *
 * Contract for Support Inquiry data access operations
 */
interface InquiryRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get inquiries by status
     *
     * @param string $status
     * @return Collection
     */
    public function getByStatus(string $status): Collection;

    /**
     * Get open inquiries count
     *
     * @return int
     */
    public function getOpenCount(): int;

    /**
     * Get inquiries assigned to employee
     *
     * @param int $employeeId
     * @return Collection
     */
    public function getAssignedTo(int $employeeId): Collection;
}

