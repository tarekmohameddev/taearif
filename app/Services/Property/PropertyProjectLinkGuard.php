<?php

declare(strict_types=1);

namespace App\Services\Property;

use App\Models\User\RealestateManagement\Property;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PropertyProjectLinkGuard
{
    public function assertProjectIdImmutable(Property $property, ?int $requestedProjectId): void
    {
        if ($requestedProjectId === null && $property->project_id === null) {
            return;
        }

        if ((int) ($property->project_id ?? 0) === (int) ($requestedProjectId ?? 0)) {
            return;
        }

        throw new HttpException(422, 'project_id cannot be changed after creation.');
    }
}
