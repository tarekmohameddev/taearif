<?php

namespace App\Helpers;

use App\Http\Controllers\Api\content\CustomerDropdownSettingController;

class CustomerDropdownHelper
{
    /**
     * Get customer dropdown settings for a user
     *
     * @param int|null $userId
     * @return array
     */
    public static function getSettings($userId = null)
    {
        if (!$userId) {
            return [
                'is_visible' => true,
                'show_login' => true,
                'show_register' => true,
                'show_dashboard' => true,
                'show_logout' => true,
            ];
        }

        return CustomerDropdownSettingController::getDropdownSettings($userId);
    }

    /**
     * Check if dropdown should be visible
     *
     * @param int|null $userId
     * @return bool
     */
    public static function isVisible($userId = null)
    {
        $settings = self::getSettings($userId);
        return $settings['is_visible'] ?? true;
    }

    /**
     * Check if login link should be shown
     *
     * @param int|null $userId
     * @return bool
     */
    public static function showLogin($userId = null)
    {
        $settings = self::getSettings($userId);
        return $settings['show_login'] ?? true;
    }

    /**
     * Check if register link should be shown
     *
     * @param int|null $userId
     * @return bool
     */
    public static function showRegister($userId = null)
    {
        $settings = self::getSettings($userId);
        return $settings['show_register'] ?? true;
    }

    /**
     * Check if dashboard link should be shown
     *
     * @param int|null $userId
     * @return bool
     */
    public static function showDashboard($userId = null)
    {
        $settings = self::getSettings($userId);
        return $settings['show_dashboard'] ?? true;
    }

    /**
     * Check if logout link should be shown
     *
     * @param int|null $userId
     * @return bool
     */
    public static function showLogout($userId = null)
    {
        $settings = self::getSettings($userId);
        return $settings['show_logout'] ?? true;
    }
}
