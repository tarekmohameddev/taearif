<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'subject',
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
    const TYPE_PASSWORD_RESET = 'password_reset';
    const TYPE_WELCOME = 'welcome';
    const TYPE_SUBSCRIPTION_EXPIRATION = 'subscription_expiration';
    const TYPE_SUBSCRIPTION_EXPIRED = 'subscription_expired';
    const TYPE_NOTIFICATION = 'notification';

    // Languages
    const LANGUAGE_ARABIC = 'ar';
    const LANGUAGE_ENGLISH = 'en';

    /**
     * Get available template types
     */
    public static function getTypes()
    {
        return [
            self::TYPE_PASSWORD_RESET => 'Password Reset',
            self::TYPE_WELCOME => 'Welcome',
            self::TYPE_SUBSCRIPTION_EXPIRATION => 'Subscription Expiration',
            self::TYPE_SUBSCRIPTION_EXPIRED => 'Subscription Expired',
            self::TYPE_NOTIFICATION => 'Notification'
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
            self::TYPE_PASSWORD_RESET => ['{name}', '{code}', '{reset_link}'],
            self::TYPE_WELCOME => ['{name}', '{email}'],
            self::TYPE_SUBSCRIPTION_EXPIRATION => ['{name}', '{package_name}', '{expiry_date}'],
            self::TYPE_SUBSCRIPTION_EXPIRED => ['{name}', '{package_name}', '{expiry_date}'],
            self::TYPE_NOTIFICATION => ['{name}', '{message}']
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
            '{message}' => 'رسالة تجريبية',
            '{reset_link}' => 'https://example.com/reset-password?code=123456&email=ahmed@example.com'
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
