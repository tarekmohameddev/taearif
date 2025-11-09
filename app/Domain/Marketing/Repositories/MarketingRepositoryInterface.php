<?php

namespace App\Domain\Marketing\Repositories;

use App\Domain\Shared\Repositories\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * Marketing Repository Interface
 *
 * Contract for Marketing/Automated Messages data access
 */
interface MarketingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get automated message by type
     *
     * @param string $type
     * @return \App\Domain\Marketing\Models\AutomatedMessage|null
     */
    public function findByType(string $type): ?\App\Domain\Marketing\Models\AutomatedMessage;

    /**
     * Get all active automated messages
     *
     * @return Collection
     */
    public function getActive(): Collection;
}

