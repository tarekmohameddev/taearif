<?php

namespace App\Domain\Support\Services;

use App\Domain\Support\Models\Inquiry;
use App\Domain\Support\Repositories\InquiryRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Inquiry Service
 *
 * Handles customer inquiry/support ticket business logic
 */
class InquiryService extends BaseService
{
    /**
     * @var InquiryRepositoryInterface
     */
    protected InquiryRepositoryInterface $inquiryRepository;

    /**
     * InquiryService constructor.
     *
     * @param InquiryRepositoryInterface $inquiryRepository
     */
    public function __construct(InquiryRepositoryInterface $inquiryRepository)
    {
        $this->inquiryRepository = $inquiryRepository;
    }

    /**
     * Get all inquiries with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllInquiries(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->inquiryRepository->getInquiries($filters, $perPage);
    }

    /**
     * Get inquiry by ID
     *
     * @param int $id
     * @return Inquiry
     * @throws ResourceNotFoundException
     */
    public function getInquiryById(int $id): Inquiry
    {
        $inquiry = $this->inquiryRepository->getInquiryById($id, ['user', 'customer']);

        if (!$inquiry) {
            throw new ResourceNotFoundException('Inquiry not found');
        }

        return $inquiry;
    }

    /**
     * Create new inquiry
     * Note: This is for admin creating inquiries on behalf of customers
     *
     * @param array $data
     * @return Inquiry
     * @throws BusinessLogicException
     */
    public function createInquiry(array $data): Inquiry
    {
        // Validate user exists
        if (!isset($data['user_id'])) {
            throw new BusinessLogicException('User ID is required', 'INQUIRY_USER_REQUIRED', 422);
        }

        // Validate customer exists if provided
        if (isset($data['customer_id'])) {
            $customer = \App\Models\ApiCustomer::find($data['customer_id']);
            if (!$customer) {
                throw new BusinessLogicException('Customer not found', 'INQUIRY_CUSTOMER_NOT_FOUND', 404);
            }
        }

        return $this->executeInTransaction(function () use ($data) {
            return Inquiry::create($data);
        });
    }

    /**
     * Update inquiry
     *
     * @param int $id
     * @param array $data
     * @return Inquiry
     * @throws ResourceNotFoundException
     */
    public function updateInquiry(int $id, array $data): Inquiry
    {
        $inquiry = $this->getInquiryById($id);

        return $this->executeInTransaction(function () use ($inquiry, $data) {
            $inquiry->update($data);
            return $inquiry->fresh(['user', 'customer']);
        });
    }

    /**
     * Delete inquiry
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteInquiry(int $id): bool
    {
        $inquiry = $this->getInquiryById($id);

        return $this->executeInTransaction(function () use ($inquiry) {
            return $inquiry->delete();
        });
    }

    /**
     * Get inquiry statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->inquiryRepository->getStatistics();
    }

    /**
     * Get inquiries by tenant/user
     *
     * @param int $userId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInquiriesByTenant(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getAllInquiries($filters, $perPage);
    }

    /**
     * Get inquiries by customer
     *
     * @param int $customerId
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInquiriesByCustomer(int $customerId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['customer_id'] = $customerId;
        return $this->getAllInquiries($filters, $perPage);
    }

    /**
     * Get inquiries by type
     *
     * @param string $type
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getInquiriesByType(string $type, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['inquiry_type'] = $type;
        return $this->getAllInquiries($filters, $perPage);
    }

    /**
     * Bulk delete inquiries
     *
     * @param array $ids
     * @return int Number of deleted inquiries
     */
    public function bulkDeleteInquiries(array $ids): int
    {
        return $this->executeInTransaction(function () use ($ids) {
            return Inquiry::whereIn('id', $ids)->delete();
        });
    }

    /**
     * Export inquiries to array
     * Can be used for CSV/Excel export
     *
     * @param array $filters
     * @return array
     */
    public function exportInquiries(array $filters = []): array
    {
        $inquiries = $this->inquiryRepository->getInquiries($filters, 10000); // Large number for export

        return $inquiries->map(function ($inquiry) {
            return [
                'ID' => $inquiry->id,
                'Tenant' => $inquiry->user ? ($inquiry->user->first_name . ' ' . $inquiry->user->last_name) : 'N/A',
                'Customer' => $inquiry->customer ? $inquiry->customer->name : 'N/A',
                'Phone' => $inquiry->phone_number ?? 'N/A',
                'Message' => $inquiry->message,
                'Inquiry Type' => $inquiry->inquiry_type ?? 'N/A',
                'Property Type' => $inquiry->property_type ?? 'N/A',
                'Budget' => $inquiry->budget ? number_format($inquiry->budget, 2) : 'N/A',
                'Location' => $inquiry->location ?? 'N/A',
                'Urgency' => $inquiry->urgency ?? 'N/A',
                'Source Channel' => $inquiry->source_channel ?? 'N/A',
                'Created At' => $inquiry->created_at ? $inquiry->created_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        })->toArray();
    }
}

