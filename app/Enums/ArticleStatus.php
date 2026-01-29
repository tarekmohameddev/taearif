<?php

namespace App\Enums;

enum ArticleStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case SCHEDULED = 'scheduled';
    case ARCHIVED = 'archived';

    /**
     * Check if status is published
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }

    /**
     * Check if status is draft
     */
    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if status is scheduled
     */
    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    /**
     * Check if status is archived
     */
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }

    /**
     * Get all status values as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
