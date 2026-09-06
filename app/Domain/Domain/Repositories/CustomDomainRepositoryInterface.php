<?php

namespace App\Domain\Domain\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Domain\Models\CustomDomain;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Custom Domain Repository Interface
 *
 * Data access for the legacy `user_custom_domains` admin API. Vercel-backed
 * domains are authoritative in `api_domains_settings` — see
 * {@see \App\Contracts\Vercel\VercelDomainSourceOfTruth}.
 */
interface CustomDomainRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate domains with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get pending domain requests
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPending();

    /**
     * Get approved domains
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApproved();

    /**
     * Get domains by user
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(int $userId);

    /**
     * Approve domain request
     *
     * @param CustomDomain $domain
     * @return CustomDomain
     */
    public function approveDomain(CustomDomain $domain): CustomDomain;

    /**
     * Reject domain request
     *
     * @param CustomDomain $domain
     * @return CustomDomain
     */
    public function rejectDomain(CustomDomain $domain): CustomDomain;

    /**
     * Toggle domain status
     *
     * @param CustomDomain $domain
     * @return CustomDomain
     */
    public function toggleStatus(CustomDomain $domain): CustomDomain;
}

