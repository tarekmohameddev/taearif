<?php

namespace App\Domain\AdminArticles\Services;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class SlugService
{
    /**
     * Generate a unique slug from text
     *
     * @param string $text
     * @param string $modelClass
     * @param int|null $excludeId
     * @return string
     */
    public function generateUniqueSlug(string $text, string $modelClass, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($text);
        $slug = $baseSlug;

        $query = $modelClass::where('slug', $slug);
        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $n = 2;
        while ($query->exists()) {
            $slug = $baseSlug . '-' . $n;
            $query = $modelClass::where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            $n++;
        }

        return $slug;
    }
}
