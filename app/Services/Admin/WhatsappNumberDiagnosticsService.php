<?php

namespace App\Services\Admin;

use App\Services\MetaGraphService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Read-only Meta Graph diagnostics for a single whatsapp_users row.
 */
class WhatsappNumberDiagnosticsService
{
    private const STATUS_OK = 'ok';
    private const STATUS_WARN = 'warn';
    private const STATUS_FAIL = 'fail';
    private const STATUS_SKIPPED = 'skipped';

    private const RECONCILIATION_ELIGIBLE = 'eligible';
    private const RECONCILIATION_NOT_NEEDED = 'not_needed';
    private const RECONCILIATION_BLOCKED = 'blocked';

    /** @var array<string,int> */
    private const STATUS_RANK = [
        self::STATUS_FAIL => 4,
        self::STATUS_WARN => 3,
        self::STATUS_OK => 2,
        self::STATUS_SKIPPED => 1,
    ];

    public function __construct(
        private MetaGraphService $metaGraph
    ) {
    }

    public function diagnose(int $whatsappUserId): array
    {
        $checkedAt = now();

        $row = DB::table('whatsapp_users')
            ->where('id', $whatsappUserId)
            ->first(['id', 'user_id', 'status', 'access_token', 'token_expires_at', 'waba_id', 'phone_id']);

        if ($row === null) {
            return [
                'checks' => [
                    $this->makeCheck(
                        'not_found',
                        self::STATUS_FAIL,
                        __('WhatsApp number not found'),
                        __('No whatsapp_users row exists for this id.')
                    ),
                ],
                'meta_phone_numbers' => [],
                'reconciliation' => $this->blockedReconciliation(null, 'not_found'),
                'summary' => self::STATUS_FAIL,
                'checked_at' => $checkedAt,
            ];
        }

        $checks = [];
        $metaPhoneNumbers = [];
        $accessToken = trim((string) ($row->access_token ?? ''));
        $tokenPresent = $accessToken !== '';
        $appTokenConfigured = trim((string) config('services.meta.app_token', '')) !== '';

        if (! $tokenPresent) {
            $checks[] = $this->makeCheck(
                'token_present',
                self::STATUS_FAIL,
                __('Access token missing'),
                __('No access token is stored for this WhatsApp number.')
            );
        } else {
            $checks[] = $this->makeCheck(
                'token_present',
                self::STATUS_OK,
                __('Access token present'),
                __('An access token is stored for this WhatsApp number.')
            );
        }

        if (! $appTokenConfigured) {
            $checks[] = $this->makeCheck(
                'app_token_configured',
                self::STATUS_FAIL,
                __('App token not configured'),
                __('Set META_APP_TOKEN in .env to enable Meta Graph diagnostics.')
            );
        } else {
            $checks[] = $this->makeCheck(
                'app_token_configured',
                self::STATUS_OK,
                __('App token configured'),
                __('META_APP_TOKEN is configured for Graph API calls.')
            );
        }

        if (! $tokenPresent || ! $appTokenConfigured) {
            $skipReason = ! $tokenPresent
                ? __('Skipped because no access token is stored.')
                : __('Skipped because META_APP_TOKEN is not configured.');

            foreach ($this->graphCheckKeys() as $key) {
                $checks[] = $this->skippedGraphCheck($key, $skipReason);
            }

            return [
                'checks' => $checks,
                'meta_phone_numbers' => $metaPhoneNumbers,
                'reconciliation' => $this->blockedReconciliation($row, $tokenPresent ? 'app_token_missing' : 'token_missing'),
                'summary' => $this->resolveSummary($checks),
                'checked_at' => $checkedAt,
            ];
        }

        $debugResponse = null;
        $debugFailed = false;

        try {
            $debugResponse = $this->metaGraph->debugToken($accessToken);
        } catch (\Throwable $e) {
            $debugFailed = true;
            $checks[] = $this->makeCheck(
                'token_valid',
                self::STATUS_FAIL,
                __('Token validity'),
                __('Meta Graph debug_token request failed: :message', [
                    'message' => $this->safeExceptionMessage($e),
                ])
            );
        }

        if (! $debugFailed) {
            $checks[] = $this->buildTokenValidCheck($debugResponse);
        }

        if ($debugFailed || $debugResponse === null) {
            $skipReason = __('Skipped because the debug_token response could not be retrieved.');

            foreach (['token_expiry', 'waba_id_match', 'phone_id_known_to_meta'] as $key) {
                $checks[] = $this->skippedGraphCheck($key, $skipReason);
            }

            return [
                'checks' => $checks,
                'meta_phone_numbers' => $metaPhoneNumbers,
                'reconciliation' => $this->blockedReconciliation($row, 'token_debug_failed'),
                'summary' => $this->resolveSummary($checks),
                'checked_at' => $checkedAt,
            ];
        }

        $tokenValid = (bool) ($debugResponse['data']['is_valid'] ?? false);
        $expiryCheck = $this->buildTokenExpiryCheck($debugResponse, $row->token_expires_at);
        $checks[] = $expiryCheck;

        $tokenWabaIds = $this->metaGraph->extractWabaIdsFromDebugToken($debugResponse);
        $phoneId = trim((string) ($row->phone_id ?? ''));
        $discovery = $tokenValid
            ? $this->discoverPhoneOwnership($accessToken, $tokenWabaIds, $phoneId)
            : [
                'check' => $this->skippedGraphCheck(
                    'phone_id_known_to_meta',
                    __('Skipped because Meta reports the access token is invalid.')
                ),
                'meta_phone_numbers' => [],
                'matched_waba_ids' => [],
                'failed_waba_ids' => [],
                'verified_waba_id' => null,
            ];

        $checks[] = $this->buildWabaIdMatchCheck($row->waba_id, $tokenWabaIds, $discovery);
        $checks[] = $discovery['check'];
        $metaPhoneNumbers = $discovery['meta_phone_numbers'];
        $reconciliation = $this->buildReconciliation(
            $row,
            $discovery,
            $tokenValid && ($expiryCheck['status'] ?? self::STATUS_FAIL) !== self::STATUS_FAIL
        );

        return [
            'checks' => $checks,
            'meta_phone_numbers' => $metaPhoneNumbers,
            'reconciliation' => $reconciliation,
            'summary' => $this->resolveSummary($checks),
            'checked_at' => $checkedAt,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function graphCheckKeys(): array
    {
        return [
            'token_valid',
            'token_expiry',
            'waba_id_match',
            'phone_id_known_to_meta',
        ];
    }

    /**
     * @return array{key:string,status:string,label:string,detail:string}
     */
    private function makeCheck(string $key, string $status, string $label, string $detail): array
    {
        return [
            'key' => $key,
            'status' => $status,
            'label' => $label,
            'detail' => $detail,
        ];
    }

    private function skippedGraphCheck(string $key, string $reason): array
    {
        return $this->makeCheck(
            $key,
            self::STATUS_SKIPPED,
            $this->labelForKey($key),
            $reason
        );
    }

    private function labelForKey(string $key): string
    {
        return match ($key) {
            'token_valid' => __('Token validity'),
            'token_expiry' => __('Token expiry'),
            'waba_id_match' => __('WABA id match'),
            'phone_id_known_to_meta' => __('Phone number known to Meta'),
            default => __('Diagnostic check'),
        };
    }

    /**
     * @param  array<string,mixed>  $debugResponse
     * @return array{key:string,status:string,label:string,detail:string}
     */
    private function buildTokenValidCheck(array $debugResponse): array
    {
        $data = $debugResponse['data'] ?? [];
        $isValid = (bool) ($data['is_valid'] ?? false);

        if ($isValid) {
            return $this->makeCheck(
                'token_valid',
                self::STATUS_OK,
                __('Token validity'),
                __('Meta reports the stored access token is valid.')
            );
        }

        $errorMessage = (string) ($debugResponse['error']['message'] ?? $data['error']['message'] ?? '');

        if ($errorMessage === '') {
            $errorMessage = __('Meta reports the stored access token is invalid.');
        }

        return $this->makeCheck(
            'token_valid',
            self::STATUS_FAIL,
            __('Token validity'),
            __('Meta reports the stored access token is invalid: :message', [
                'message' => $errorMessage,
            ])
        );
    }

    /**
     * @param  array<string,mixed>  $debugResponse
     * @return array{key:string,status:string,label:string,detail:string}
     */
    private function buildTokenExpiryCheck(array $debugResponse, $storedExpiresAt): array
    {
        $data = $debugResponse['data'] ?? [];
        $expiresAt = $data['expires_at'] ?? null;

        if ($expiresAt === null || (int) $expiresAt === 0) {
            return $this->makeCheck(
                'token_expiry',
                self::STATUS_OK,
                __('Token expiry'),
                __('Meta reports this token never expires.')
            );
        }

        $expiresCarbon = Carbon::createFromTimestamp((int) $expiresAt);
        $formattedExpiry = $expiresCarbon->format('Y-m-d H:i');
        $detailParts = [__('Meta expiry: :date', ['date' => $formattedExpiry])];

        if ($storedExpiresAt !== null && $storedExpiresAt !== '') {
            $storedCarbon = Carbon::parse($storedExpiresAt);
            $deltaDays = abs($storedCarbon->diffInDays($expiresCarbon, false));

            if ($deltaDays > 1) {
                $detailParts[] = __('Stored token_expires_at (:date) differs from Meta by more than one day.', [
                    'date' => $storedCarbon->format('Y-m-d H:i'),
                ]);
            }
        }

        if ($expiresCarbon->isPast()) {
            return $this->makeCheck(
                'token_expiry',
                self::STATUS_FAIL,
                __('Token expiry'),
                implode(' ', $detailParts) . ' ' . __('The token has expired.')
            );
        }

        if ($expiresCarbon->lessThanOrEqualTo(now()->addDays(7))) {
            return $this->makeCheck(
                'token_expiry',
                self::STATUS_WARN,
                __('Token expiry'),
                implode(' ', $detailParts) . ' ' . __('The token expires within 7 days.')
            );
        }

        return $this->makeCheck(
            'token_expiry',
            self::STATUS_OK,
            __('Token expiry'),
            implode(' ', $detailParts)
        );
    }

    /**
     * @param  array<int,string>  $tokenWabaIds
     * @param  array<string,mixed>  $discovery
     */
    private function buildWabaIdMatchCheck($storedWabaId, array $tokenWabaIds, array $discovery): array
    {
        $stored = trim((string) ($storedWabaId ?? ''));
        $verified = (string) ($discovery['verified_waba_id'] ?? '');

        if ($tokenWabaIds === []) {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_FAIL,
                __('WABA id match'),
                __('The token lacks WhatsApp granular scopes, so no WABA id could be extracted.')
            );
        }

        if ($verified === '') {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_FAIL,
                __('WABA id match'),
                __('A unique WABA could not be verified from the stored phone ID.')
            );
        }

        if ($stored === '') {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_WARN,
                __('WABA id match'),
                __('Verified WABA id :verified_waba is not stored locally and can be reconciled.', [
                    'verified_waba' => $verified,
                ])
            );
        }

        if ($stored === $verified) {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_OK,
                __('WABA id match'),
                __('Stored waba_id matches the WABA that owns this phone (:waba).', ['waba' => $stored])
            );
        }

        return $this->makeCheck(
            'waba_id_match',
            self::STATUS_FAIL,
            __('WABA id match'),
            __('Stored waba_id (:stored) differs from the WABA verified for this phone (:verified).', [
                'stored' => $stored,
                'verified' => $verified,
            ])
        );
    }

    /**
     * @param  array<int,string>  $wabaIds
     * @return array<string,mixed>
     */
    private function discoverPhoneOwnership(string $accessToken, array $wabaIds, string $phoneId): array
    {
        $metaPhoneNumbers = [];
        $matchedWabaIds = [];
        $failedWabaIds = [];

        foreach ($wabaIds as $wabaId) {
            try {
                $response = $this->metaGraph->listPhoneNumbers($accessToken, $wabaId);
                $phones = $this->normalizePhoneNumbers($response, $wabaId);
                $metaPhoneNumbers = array_merge($metaPhoneNumbers, $phones);

                foreach ($phones as $phone) {
                    if ($phoneId !== '' && ($phone['id'] ?? '') === $phoneId) {
                        $matchedWabaIds[$wabaId] = $wabaId;
                    }
                }
            } catch (\Throwable $e) {
                $failedWabaIds[$wabaId] = $wabaId;
            }
        }

        $matchedWabaIds = array_values($matchedWabaIds);
        $failedWabaIds = array_values($failedWabaIds);
        $verifiedWabaId = count($matchedWabaIds) === 1 && $failedWabaIds === []
            ? $matchedWabaIds[0]
            : null;

        if ($wabaIds === []) {
            $check = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_SKIPPED,
                __('Phone number known to Meta'),
                __('Skipped because the token grants no WhatsApp Business Account IDs.')
            );
        } elseif ($failedWabaIds !== []) {
            $check = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_FAIL,
                __('Phone number known to Meta'),
                __('Meta phone lookup was incomplete for :count WABA account(s). No repair is allowed.', [
                    'count' => (string) count($failedWabaIds),
                ])
            );
        } elseif ($phoneId === '') {
            $check = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_FAIL,
                __('Phone number known to Meta'),
                __('No phone_id is stored locally, so WABA ownership cannot be verified.')
            );
        } elseif (count($matchedWabaIds) === 1) {
            $check = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_OK,
                __('Phone number known to Meta'),
                __('Stored phone_id belongs to exactly one accessible WABA (:waba).', [
                    'waba' => $matchedWabaIds[0],
                ])
            );
        } elseif ($matchedWabaIds === []) {
            $check = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_FAIL,
                __('Phone number known to Meta'),
                __('Stored phone_id was not found in any WABA accessible to this token.')
            );
        } else {
            $check = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_FAIL,
                __('Phone number known to Meta'),
                __('Stored phone_id matched more than one WABA. Automatic repair is blocked.')
            );
        }

        return [
            'check' => $check,
            'meta_phone_numbers' => $metaPhoneNumbers,
            'matched_waba_ids' => $matchedWabaIds,
            'failed_waba_ids' => $failedWabaIds,
            'verified_waba_id' => $verifiedWabaId,
        ];
    }

    /**
     * @param  array<string,mixed>  $response
     * @return array<int,array{id:string,waba_id:string,display_phone_number:string,verified_name:string,quality_rating:string}>
     */
    private function normalizePhoneNumbers(array $response, string $wabaId): array
    {
        $items = $response['data'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        $normalized = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $normalized[] = [
                'id' => (string) ($item['id'] ?? ''),
                'waba_id' => $wabaId,
                'display_phone_number' => (string) ($item['display_phone_number'] ?? ''),
                'verified_name' => (string) ($item['verified_name'] ?? ''),
                'quality_rating' => (string) ($item['quality_rating'] ?? ''),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string,mixed>  $discovery
     * @return array<string,mixed>
     */
    private function buildReconciliation(object $row, array $discovery, bool $tokenUsable): array
    {
        $base = [
            'state' => self::RECONCILIATION_BLOCKED,
            'reason_code' => 'unverified',
            'stored_waba_id' => trim((string) ($row->waba_id ?? '')),
            'verified_waba_id' => $discovery['verified_waba_id'] ?? null,
            'phone_id' => trim((string) ($row->phone_id ?? '')),
        ];

        if ((string) ($row->status ?? '') !== 'active') {
            $base['reason_code'] = 'number_not_active';

            return $base;
        }

        if ($this->hasWaNumberOwnerMismatch($row)) {
            $base['reason_code'] = 'owner_mismatch';

            return $base;
        }

        if (! $tokenUsable) {
            $base['reason_code'] = 'token_not_usable';

            return $base;
        }

        if (($discovery['failed_waba_ids'] ?? []) !== []) {
            $base['reason_code'] = 'meta_lookup_incomplete';

            return $base;
        }

        $matches = $discovery['matched_waba_ids'] ?? [];

        if ($base['phone_id'] === '') {
            $base['reason_code'] = 'phone_id_missing';

            return $base;
        }

        if (count($matches) === 0) {
            $base['reason_code'] = 'phone_not_found';

            return $base;
        }

        if (count($matches) !== 1 || empty($base['verified_waba_id'])) {
            $base['reason_code'] = 'ambiguous_phone_ownership';

            return $base;
        }

        if ($base['stored_waba_id'] === (string) $base['verified_waba_id']) {
            $base['state'] = self::RECONCILIATION_NOT_NEEDED;
            $base['reason_code'] = 'already_matches';

            return $base;
        }

        $base['state'] = self::RECONCILIATION_ELIGIBLE;
        $base['reason_code'] = 'unique_phone_match';

        return $base;
    }

    private function hasWaNumberOwnerMismatch(object $row): bool
    {
        $phoneId = trim((string) ($row->phone_id ?? ''));

        if ($phoneId === '') {
            return false;
        }

        return DB::table('wa_numbers')
            ->where('provider', 'meta')
            ->where('phone_number_id', $phoneId)
            ->where('user_id', '<>', (int) ($row->user_id ?? 0))
            ->exists();
    }

    /**
     * @return array<string,mixed>
     */
    private function blockedReconciliation(?object $row, string $reasonCode): array
    {
        return [
            'state' => self::RECONCILIATION_BLOCKED,
            'reason_code' => $reasonCode,
            'stored_waba_id' => trim((string) ($row->waba_id ?? '')),
            'verified_waba_id' => null,
            'phone_id' => trim((string) ($row->phone_id ?? '')),
        ];
    }

    /**
     * @param  array<int,array{key:string,status:string,label:string,detail:string}>  $checks
     */
    private function resolveSummary(array $checks): string
    {
        $worst = self::STATUS_SKIPPED;

        foreach ($checks as $check) {
            $status = (string) ($check['status'] ?? self::STATUS_SKIPPED);

            if ((self::STATUS_RANK[$status] ?? 0) > (self::STATUS_RANK[$worst] ?? 0)) {
                $worst = $status;
            }
        }

        return $worst;
    }

    private function safeExceptionMessage(\Throwable $e): string
    {
        $message = trim($e->getMessage());

        if ($message === '') {
            return __('An unexpected error occurred.');
        }

        return $message;
    }
}
