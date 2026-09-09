<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardPresenceService
{
    public const DEFINITION = 'distinct_authenticated_dashboard_accounts';

    /** @var array<string, true> */
    private static array $configurationWarningsLogged = [];

    /** @var array<string, true> */
    private static array $runtimeWarningsLogged = [];

    public function __construct(
        private readonly RedisFactory $redis,
        private readonly DashboardPresenceEligibilityService $eligibility
    ) {
    }

    /**
     * @internal Reset once-per-process configuration warning guards (tests).
     */
    public static function resetConfigurationWarnings(): void
    {
        self::$configurationWarningsLogged = [];
        self::$runtimeWarningsLogged = [];
    }

    public function record(User $user, ?CarbonImmutable $clock = null): array
    {
        $eligibility = $this->eligibility->resolve($user);

        if (! $eligibility['eligible']) {
            return [
                'recorded' => false,
                'available' => true,
                'reason' => $eligibility['reason'],
                'recorded_at' => null,
            ];
        }

        $settings = $this->settings();

        if (! $settings['valid']) {
            return [
                'recorded' => false,
                'available' => false,
                'reason' => $settings['reason'],
                'recorded_at' => null,
            ];
        }

        $recordedAt = ($clock ?? CarbonImmutable::now('UTC'))->setTimezone('UTC');
        $timestamp = $recordedAt->getTimestamp();
        $ttlSeconds = max($settings['online_window_seconds'] + 60, $settings['online_window_seconds'] * 3);

        try {
            $connection = $this->redis->connection($settings['redis_connection']);
            $connection->pipeline(function ($pipe) use ($settings, $user, $eligibility, $timestamp, $ttlSeconds): void {
                $pipe->zadd($settings['users_key'], $timestamp, (string) $user->id);
                $pipe->zadd($settings['tenant_organizations_key'], $timestamp, (string) $eligibility['tenant_owner_id']);
                $pipe->expire($settings['users_key'], $ttlSeconds);
                $pipe->expire($settings['tenant_organizations_key'], $ttlSeconds);
            });
        } catch (Throwable $e) {
            $this->logRuntimeIssueOnce('record', $e);

            return [
                'recorded' => false,
                'available' => false,
                'reason' => 'redis_unavailable',
                'recorded_at' => null,
            ];
        }

        return [
            'recorded' => true,
            'available' => true,
            'reason' => null,
            'recorded_at' => $recordedAt->toIso8601String(),
        ];
    }

    public function snapshot(?CarbonImmutable $clock = null): array
    {
        $settings = $this->settings();
        $asOf = ($clock ?? CarbonImmutable::now('UTC'))->setTimezone('UTC');

        if (! $settings['valid']) {
            return [
                'available' => false,
                'online_users' => null,
                'online_tenant_organizations' => null,
                'window_seconds' => $settings['online_window_seconds'] ?? null,
                'as_of' => $asOf->toIso8601String(),
                'definition' => self::DEFINITION,
                'reason' => $settings['reason'],
            ];
        }

        $cutoff = $asOf->getTimestamp() - $settings['online_window_seconds'];

        try {
            $connection = $this->redis->connection($settings['redis_connection']);
            $counts = $connection->pipeline(function ($pipe) use ($settings, $cutoff): void {
                $pipe->zremrangebyscore($settings['users_key'], '-inf', (string) $cutoff);
                $pipe->zremrangebyscore($settings['tenant_organizations_key'], '-inf', (string) $cutoff);
                $pipe->zcard($settings['users_key']);
                $pipe->zcard($settings['tenant_organizations_key']);
            });
        } catch (Throwable $e) {
            $this->logRuntimeIssueOnce('snapshot', $e);

            return [
                'available' => false,
                'online_users' => null,
                'online_tenant_organizations' => null,
                'window_seconds' => $settings['online_window_seconds'],
                'as_of' => $asOf->toIso8601String(),
                'definition' => self::DEFINITION,
                'reason' => 'redis_unavailable',
            ];
        }

        return [
            'available' => true,
            'online_users' => (int) ($counts[2] ?? 0),
            'online_tenant_organizations' => (int) ($counts[3] ?? 0),
            'window_seconds' => $settings['online_window_seconds'],
            'as_of' => $asOf->toIso8601String(),
            'definition' => self::DEFINITION,
            'reason' => null,
        ];
    }

    private function settings(): array
    {
        $enabled = (bool) config('dashboard-presence.enabled', true);
        $heartbeatSeconds = (int) config('dashboard-presence.heartbeat_seconds', 45);
        $windowSeconds = (int) config('dashboard-presence.online_window_seconds', 120);
        $adminPollSeconds = (int) config('dashboard-presence.admin_poll_seconds', 30);
        $redisConnection = (string) config('dashboard-presence.redis_connection', 'cache');
        $usersKey = trim((string) config('dashboard-presence.users_key', 'presence:dashboard:users'));
        $tenantOrganizationsKey = trim((string) config('dashboard-presence.tenant_organizations_key', 'presence:dashboard:tenant-organizations'));

        if (! $enabled) {
            $this->logConfigurationIssueOnce('disabled', [
                'online_window_seconds' => $windowSeconds,
            ]);

            return [
                'valid' => false,
                'reason' => 'disabled',
                'online_window_seconds' => $windowSeconds,
            ];
        }

        $validHeartbeat = $heartbeatSeconds >= 15 && $heartbeatSeconds <= 300;
        $validWindow = $windowSeconds >= 60 && $windowSeconds <= 900 && $windowSeconds >= ($heartbeatSeconds * 2);
        $validPoll = $adminPollSeconds >= 15 && $adminPollSeconds <= 300;

        if (! $validHeartbeat || ! $validWindow || ! $validPoll || $redisConnection === '' || $usersKey === '' || $tenantOrganizationsKey === '') {
            $this->logConfigurationIssueOnce('invalid_configuration', [
                'heartbeat_seconds' => $heartbeatSeconds,
                'online_window_seconds' => $windowSeconds,
                'admin_poll_seconds' => $adminPollSeconds,
                'redis_connection' => $redisConnection,
            ]);

            return [
                'valid' => false,
                'reason' => 'invalid_configuration',
                'online_window_seconds' => $windowSeconds,
            ];
        }

        return [
            'valid' => true,
            'reason' => null,
            'heartbeat_seconds' => $heartbeatSeconds,
            'online_window_seconds' => $windowSeconds,
            'admin_poll_seconds' => $adminPollSeconds,
            'redis_connection' => $redisConnection,
            'users_key' => $usersKey,
            'tenant_organizations_key' => $tenantOrganizationsKey,
        ];
    }

    private function logConfigurationIssueOnce(string $reason, array $context = []): void
    {
        if (isset(self::$configurationWarningsLogged[$reason])) {
            return;
        }

        self::$configurationWarningsLogged[$reason] = true;

        Log::warning('Dashboard presence unavailable due to configuration.', array_merge([
            'reason' => $reason,
        ], $context));
    }

    private function logRuntimeIssueOnce(string $operation, Throwable $exception): void
    {
        if (isset(self::$runtimeWarningsLogged[$operation])) {
            return;
        }

        self::$runtimeWarningsLogged[$operation] = true;

        Log::warning('Dashboard presence unavailable due to a Redis error.', [
            'operation' => $operation,
            'exception' => $exception::class,
        ]);
    }
}
