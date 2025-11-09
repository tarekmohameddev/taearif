<?php

namespace App\Domain\Marketing\Repositories;

use App\Domain\Marketing\Models\WhatsAppTemplate;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * WhatsApp Template Repository
 *
 * Handles data access for WhatsAppTemplate model
 */
class WhatsAppTemplateRepository extends BaseRepository implements WhatsAppTemplateRepositoryInterface
{
    /**
     * WhatsAppTemplateRepository constructor.
     *
     * @param WhatsAppTemplate $model
     */
    public function __construct(WhatsAppTemplate $model)
    {
        parent::__construct($model);
    }

    /**
     * Search and paginate templates with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchAndPaginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->model->query();

        // Search filter
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active' || $filters['status'] === '1' || $filters['status'] === 1) {
                $query->active();
            } elseif ($filters['status'] === 'inactive' || $filters['status'] === '0' || $filters['status'] === 0) {
                $query->inactive();
            }
        }

        // Type filter
        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        // Language filter
        if (!empty($filters['language'])) {
            $query->byLanguage($filters['language']);
        }

        // Date range filter
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        // Order by
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get active templates
     *
     * @return Collection
     */
    public function getActive(): Collection
    {
        return $this->model
            ->active()
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get templates by type
     *
     * @param string $type
     * @return Collection
     */
    public function getByType(string $type): Collection
    {
        return $this->model
            ->byType($type)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get templates by language
     *
     * @param string $language
     * @return Collection
     */
    public function getByLanguage(string $language): Collection
    {
        return $this->model
            ->byLanguage($language)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Toggle template status
     *
     * @param WhatsAppTemplate $template
     * @return WhatsAppTemplate
     */
    public function toggleStatus(WhatsAppTemplate $template): WhatsAppTemplate
    {
        $template->update([
            'status' => !$template->status,
        ]);

        return $template->fresh();
    }

    /**
     * Apply search logic
     *
     * @param $query
     * @param string $search
     * @return mixed
     */
    protected function applySearch($query, string $search)
    {
        return $query->search($search);
    }
}

