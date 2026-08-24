<?php

namespace App\Imports\Support;

class IdRemapper
{
    /** @var array<string, array<int, int>> */
    private $map = [
        'project' => [],
        'customer' => [],
        'property' => [],
    ];

    public function put(string $type, ?int $oldId, ?int $newId): void
    {
        if ($oldId && $newId) {
            $this->map[$type][$oldId] = $newId;
        }
    }

    /**
     * @param  mixed  $oldId
     */
    public function get(string $type, $oldId): ?int
    {
        $oldId = (int) $oldId;

        return $oldId ? ($this->map[$type][$oldId] ?? null) : null;
    }
}
