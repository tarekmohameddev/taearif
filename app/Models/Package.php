<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $table = "packages";

    protected $fillable = [
        'title',
        'icon',
        'subtitle',
        'slug',
        'price',
        'term',
        'featured',
        'is_trial',
        'trial_days',
        'status',
        'new_features',
        'features',
        'meta_keywords',
        'meta_description',
        'number_of_vcards',
        'project_limit_number',
        'real_estate_limit_number',
        'video_size_limit',
        'file_size_limit',
        'serial_number'
    ];

    public function memberships()
    {
        return $this->hasMany('App\Models\Membership');
    }
}
