<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'description',
        'content',
        'type',
        'language',
        'variables',
        'status',
        'character_count'
    ];

    protected $casts = [
        'variables' => 'array',
        'status' => 'boolean',
        'character_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // Template types
    const TYPE_WELCOME = 'welcome';
    const TYPE_SUBSCRIPTION_EXPIRATION = 'subscription_expiration';
    const TYPE_PASSWORD_RESET = 'password_reset';

    // Languages
    const LANGUAGE_ARABIC = 'ar';
    const LANGUAGE_ENGLISH = 'en';

    /**
     * Get available template types
     */
    public static function getTypes()
    {
        return [
            self::TYPE_WELCOME => 'Welcome Message',
            self::TYPE_SUBSCRIPTION_EXPIRATION => 'Subscription Expiration',
            self::TYPE_PASSWORD_RESET => 'Password Reset'
        ];
    }

    /**
     * Get available languages
     */
    public static function getLanguages()
    {
        return [
            self::LANGUAGE_ARABIC => 'Arabic',
            self::LANGUAGE_ENGLISH => 'English'
        ];
    }

    /**
     * Get available variables for each template type
     */
    public static function getVariablesForType($type)
    {
        $variables = [
            self::TYPE_WELCOME => ['{name}', '{email}'],
            self::TYPE_SUBSCRIPTION_EXPIRATION => ['{name}', '{package_name}', '{expiry_date}'],
            self::TYPE_PASSWORD_RESET => ['{name}', '{code}']
        ];

        return $variables[$type] ?? [];
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for specific language
     */
    public function scopeOfLanguage($query, $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Get template type label
     */
    public function getTypeLabelAttribute()
    {
        return self::getTypes()[$this->type] ?? $this->type;
    }

    /**
     * Get language label
     */
    public function getLanguageLabelAttribute()
    {
        return self::getLanguages()[$this->language] ?? $this->language;
    }

    /**
     * Get preview content with sample data
     */
    public function getPreviewContentAttribute()
    {
        $content = $this->content;
        $variables = self::getVariablesForType($this->type);
        
        $sampleData = [
            '{name}' => 'أحمد محمد',
            '{email}' => 'ahmed@example.com',
            '{code}' => '123456',
            '{package_name}' => 'الباقة المميزة',
            '{expiry_date}' => '2024-12-31'
        ];

        foreach ($sampleData as $variable => $value) {
            if (in_array($variable, $variables)) {
                $content = str_replace($variable, $value, $content);
            }
        }

        return $content;
    }

    /**
     * Validate template content
     */
    public function validateContent()
    {
        $errors = [];
        $variables = self::getVariablesForType($this->type);
        
        foreach ($variables as $variable) {
            if (!str_contains($this->content, $variable)) {
                $errors[] = "Variable {$variable} is missing from content";
            }
        }

        return $errors;
    }
}