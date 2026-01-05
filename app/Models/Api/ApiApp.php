<?php

namespace App\Models\Api;

use App\Models\Api\ApiInstallation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\BillingType;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ApiApp extends Model
{
    use HasFactory;
    protected $table = 'api_apps';

    protected $fillable = [
        'name',
        'description',
        'price',
        'type',
        'rating',
        'img',
        'billing_type',
        'trial_days',
        'is_enabled',
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'rating' => 'float',
        'trial_days' => 'integer',
        'billing_type' => BillingType::class,
        'is_enabled' => 'boolean',
    ];
    protected $attributes = [
        'type' => 'marketplace',
        'rating' => 0,
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Before deleting, check for installations (safeguard)
        static::deleting(function ($app) {
            // Log warning if app has installations (should be prevented by service layer)
            if ($app->installations()->count() > 0) {
                Log::warning('Attempted to delete marketplace app with installations', [
                    'app_id' => $app->id,
                    'installations_count' => $app->installations()->count(),
                ]);
                // Note: Service layer should prevent this, but if deletion happens directly,
                // we log it. The service layer will throw an exception.
            }
        });

        // After deleting, clean up image file
        static::deleted(function ($app) {
            // Only delete if it's a local file in our directory
            if ($app->img && strpos($app->img, 'assets/front/img/marketplace-apps/') !== false) {
                try {
                    $fullPath = public_path($app->img);
                    if (File::exists($fullPath)) {
                        File::delete($fullPath);
                        Log::info('Deleted marketplace app image file', [
                            'app_id' => $app->id,
                            'image_path' => $app->img,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Log error but don't throw - file deletion failure shouldn't break the flow
                    Log::warning('Failed to delete marketplace app image file', [
                        'app_id' => $app->id,
                        'image_path' => $app->img,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    public function installations()
    {
        return $this->hasMany(ApiInstallation::class, 'app_id');
    }
}
