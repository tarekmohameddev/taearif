<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappUser extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_users';


    protected $primaryKey = 'id';

  
    protected $fillable = [
        'user_id', 
        'number', 
        'name', 
        'note', 
        'status', 
        'token', 
        'phone_id', 
        'business_id',
    ];



    // Optionally, cast the 'status' to a specific type (e.g., a string)
    protected $casts = [
        'status' => 'string',
        'request_status' => 'string',
    ];

    // Define the relationship with the User model (if needed)
    public function user()
    {
        return $this->belongsTo(User::class); // Assuming there's a User model
    }


}
