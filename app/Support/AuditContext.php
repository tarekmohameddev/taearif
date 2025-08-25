<?php

namespace App\Support;

use Illuminate\Support\Facades\App;

class AuditContext
{
    protected ?int $actorId = null;
    protected string $actorType = 'tenant'; // 'employee' | 'tenant' | 'system'
    protected ?string $ip_address = null;
    protected ?string $ua = null;
    protected ?int $tenantId = null;

    public static function instance(): self {
        return App::singleton(self::class, fn() => new self) ?? App::make(self::class);
    }

    public static function set(?int $actorId, string $actorType, ?int $tenantId, ?string $ip_address = null, ?string $ua = null): void {
        $ctx = app(self::class);
        $ctx->actorId   = $actorId;
        $ctx->actorType = $actorType;
        $ctx->tenantId  = $tenantId;
        $ctx->ip_address        = $ip_address;
        $ctx->ua        = $ua;
    }

    public static function asSystem(?int $tenantId = null): void {
        self::set(null, 'system', $tenantId, null, null);
    }

    public static function data(): array {
        $ctx = app(self::class);
        return [
            'actor_id'   => $ctx->actorId,
            'actor_type' => $ctx->actorType,
            'tenant_id'  => $ctx->tenantId,
            'ip_address'         => $ctx->ip_address,
            'user_agent' => $ctx->ua,
        ];
    }
}
