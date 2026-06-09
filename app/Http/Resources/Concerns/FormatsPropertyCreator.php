<?php

namespace App\Http\Resources\Concerns;

use App\Models\User;

trait FormatsPropertyCreator
{
    protected function formatCreator(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return [
            'id' => $user->id,
            'name' => $name !== '' ? $name : ($user->username ?? $user->email),
            'type' => $user->account_type,
        ];
    }
}
