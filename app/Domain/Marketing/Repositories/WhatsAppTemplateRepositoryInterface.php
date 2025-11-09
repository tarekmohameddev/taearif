<?php

namespace App\Domain\Marketing\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use App\Domain\Marketing\Models\WhatsAppTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * WhatsApp Template Repository Interface
 *
 * Defines the contract for WhatsAppTemplate data access operations
 */
interface WhatsAppTemplateRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Search and paginate templates with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get active templates
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActive();

    /**
     * Get templates by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByType(string $type);

    /**
     * Get templates by language
     *
     * @param string $language
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByLanguage(string $language);

    /**
     * Toggle template status
     *
     * @param WhatsAppTemplate $template
     * @return WhatsAppTemplate
     */
    public function toggleStatus(WhatsAppTemplate $template): WhatsAppTemplate;
}

