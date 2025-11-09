<?php

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\Setting;

/**
 * Setting Repository Interface
 *
 * Contract for Settings data access operations
 */
interface SettingRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get setting by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set setting value
     *
     * @param string $key
     * @param mixed $value
     * @return Setting
     */
    public function set(string $key, mixed $value): Setting;

    /**
     * Get settings by group
     *
     * @param string $group
     * @return array
     */
    public function getByGroup(string $group): array;

    /**
     * Bulk update settings
     *
     * @param array $settings
     * @return bool
     */
    public function bulkUpdate(array $settings): bool;
}

