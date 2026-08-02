<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

final class AiProviderCredential extends Model
{
    protected $table = 'ai_provider_credentials';

    protected $hidden = ['api_key_encrypted'];

    protected $fillable = [
        'user_id',
        'provider',
        'base_url',
        'api_key_encrypted',
        'chat_model',
        'fast_model',
        'embedding_model',
        'allowed_models',
        'is_platform_default',
        'active',
    ];

    protected $casts = [
        'allowed_models'      => 'array',
        'is_platform_default' => 'boolean',
        'active'              => 'boolean',
    ];

    public function getDecryptedKey(): string
    {
        return Crypt::decryptString($this->api_key_encrypted);
    }

    public static function storeKey(self $credential, string $rawKey): void
    {
        $credential->api_key_encrypted = Crypt::encryptString($rawKey);
    }
}
