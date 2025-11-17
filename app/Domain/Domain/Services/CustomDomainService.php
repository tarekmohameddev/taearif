<?php

namespace App\Domain\Domain\Services;

use App\Domain\Domain\Models\CustomDomain;
use App\Domain\Domain\Repositories\CustomDomainRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Custom Domain Service
 * 
 * Business logic for managing custom domains
 */
class CustomDomainService extends BaseService
{
    /**
     * @var CustomDomainRepositoryInterface
     */
    protected $domainRepository;

    /**
     * CustomDomainService constructor.
     *
     * @param CustomDomainRepositoryInterface $domainRepository
     */
    public function __construct(CustomDomainRepositoryInterface $domainRepository)
    {
        $this->domainRepository = $domainRepository;
    }

    /**
     * Get paginated domains with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDomains(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->domainRepository->searchAndPaginate($filters, $perPage);
    }

    /**
     * Get domain by ID
     *
     * @param int $id
     * @return CustomDomain
     * @throws ResourceNotFoundException
     */
    public function getDomainById(int $id): CustomDomain
    {
        $domain = $this->domainRepository->findById($id);

        if (!$domain) {
            throw new ResourceNotFoundException('Domain not found');
        }

        $domain->load(['user']);

        return $domain;
    }

    /**
     * Create a new custom domain
     *
     * @param array $data
     * @return CustomDomain
     */
    public function createDomain(array $data): CustomDomain
    {
        return $this->transaction(function () use ($data) {
            // Ensure status has a default value if not provided
            $data['status'] = $data['status'] ?? false;
            
            $domain = $this->domainRepository->create($data);
            
            return $domain->load(['user']);
        });
    }

    /**
     * Update existing domain
     *
     * @param int $id
     * @param array $data
     * @return CustomDomain
     * @throws ResourceNotFoundException
     */
    public function updateDomain(int $id, array $data): CustomDomain
    {
        $domain = $this->domainRepository->findById($id);

        if (!$domain) {
            throw new ResourceNotFoundException('Domain not found');
        }

        return $this->transaction(function () use ($domain, $data) {
            $updated = $this->domainRepository->update($domain, $data);
            
            return $updated->load(['user']);
        });
    }

    /**
     * Delete a domain
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteDomain(int $id): bool
    {
        $domain = $this->domainRepository->findById($id);

        if (!$domain) {
            throw new ResourceNotFoundException('Domain not found');
        }

        return $this->transaction(function () use ($domain) {
            return $this->domainRepository->delete($domain);
        });
    }

    /**
     * Approve domain request
     *
     * @param int $id
     * @return CustomDomain
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function approveDomain(int $id): CustomDomain
    {
        $domain = $this->domainRepository->findById($id);

        if (!$domain) {
            throw new ResourceNotFoundException('Domain not found');
        }

        if ($domain->isApproved()) {
            throw new BusinessLogicException(
                'Domain is already approved',
                'DOMAIN_ALREADY_APPROVED',
                422
            );
        }

        if (!$domain->requested_domain) {
            throw new BusinessLogicException(
                'No domain request to approve',
                'DOMAIN_REQUEST_MISSING',
                422
            );
        }

        return $this->transaction(function () use ($domain) {
            return $this->domainRepository->approveDomain($domain);
        });
    }

    /**
     * Reject domain request
     *
     * @param int $id
     * @return CustomDomain
     * @throws ResourceNotFoundException
     */
    public function rejectDomain(int $id): CustomDomain
    {
        $domain = $this->domainRepository->findById($id);

        if (!$domain) {
            throw new ResourceNotFoundException('Domain not found');
        }

        return $this->transaction(function () use ($domain) {
            return $this->domainRepository->rejectDomain($domain);
        });
    }

    /**
     * Toggle domain status
     *
     * @param int $id
     * @return CustomDomain
     * @throws ResourceNotFoundException
     */
    public function toggleStatus(int $id): CustomDomain
    {
        $domain = $this->domainRepository->findById($id);

        if (!$domain) {
            throw new ResourceNotFoundException('Domain not found');
        }

        return $this->transaction(function () use ($domain) {
            return $this->domainRepository->toggleStatus($domain);
        });
    }

    /**
     * Get pending domain requests
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPendingDomains()
    {
        return $this->domainRepository->getPending();
    }

    /**
     * Get approved domains
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApprovedDomains()
    {
        return $this->domainRepository->getApproved();
    }

    /**
     * Get domains statistics
     *
     * @return array
     */
    public function getDomainStatistics(): array
    {
        return [
            'total_domains' => $this->domainRepository->count(),
            'active_domains' => CustomDomain::active()->count(),
            'inactive_domains' => CustomDomain::inactive()->count(),
            'pending_requests' => CustomDomain::pending()->count(),
            'approved_domains' => CustomDomain::approved()->count(),
        ];
    }
}

