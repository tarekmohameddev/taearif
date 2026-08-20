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
            ->first(['id', 'access_token', 'token_expires_at', 'waba_id', 'phone_id']);

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
                'summary' => $this->resolveSummary($checks),
                'checked_at' => $checkedAt,
            ];
        }

        $checks[] = $this->buildTokenExpiryCheck($debugResponse, $row->token_expires_at);
        $checks[] = $this->buildWabaIdMatchCheck($debugResponse, $row->waba_id);

        $tokenWabaId = $this->metaGraph->extractWabaIdFromDebugToken($debugResponse);
        $wabaForPhones = $tokenWabaId ?: trim((string) ($row->waba_id ?? ''));

        if ($wabaForPhones === '') {
            $checks[] = $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_SKIPPED,
                __('Phone number known to Meta'),
                __('Skipped because no WhatsApp Business Account id is available from the token or database.')
            );
        } else {
            $phoneCheck = $this->buildPhoneIdKnownCheck(
                $accessToken,
                $wabaForPhones,
                trim((string) ($row->phone_id ?? ''))
            );
            $checks[] = $phoneCheck['check'];
            $metaPhoneNumbers = $phoneCheck['meta_phone_numbers'];
        }

        return [
            'checks' => $checks,
            'meta_phone_numbers' => $metaPhoneNumbers,
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
     * @param  array<string,mixed>  $debugResponse
     * @return array{key:string,status:string,label:string,detail:string}
     */
    private function buildWabaIdMatchCheck(array $debugResponse, $storedWabaId): array
    {
        $tokenWabaId = $this->metaGraph->extractWabaIdFromDebugToken($debugResponse);
        $stored = trim((string) ($storedWabaId ?? ''));

        if ($tokenWabaId === null || $tokenWabaId === '') {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_FAIL,
                __('WABA id match'),
                __('The token lacks WhatsApp granular scopes, so no WABA id could be extracted.')
            );
        }

        if ($stored === '') {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_WARN,
                __('WABA id match'),
                __('Token WABA id :token_waba is not stored locally and can be backfilled.', [
                    'token_waba' => $tokenWabaId,
                ])
            );
        }

        if ($stored === $tokenWabaId) {
            return $this->makeCheck(
                'waba_id_match',
                self::STATUS_OK,
                __('WABA id match'),
                __('Stored waba_id matches the token WABA id (:waba).', ['waba' => $stored])
            );
        }

        return $this->makeCheck(
            'waba_id_match',
            self::STATUS_FAIL,
            __('WABA id match'),
            __('Stored waba_id (:stored) differs from token WABA id (:token).', [
                'stored' => $stored,
                'token' => $tokenWabaId,
            ])
        );
    }

    /**
     * @return array{check:array{key:string,status:string,label:string,detail:string},meta_phone_numbers:array<int,array<string,string|null>>}
     */
    private function buildPhoneIdKnownCheck(string $accessToken, string $wabaId, string $phoneId): array
    {
        try {
            $response = $this->metaGraph->listPhoneNumbers($accessToken, $wabaId);
        } catch (\Throwable $e) {
            return [
                'check' => $this->makeCheck(
                    'phone_id_known_to_meta',
                    self::STATUS_FAIL,
                    __('Phone number known to Meta'),
                    __('Meta Graph phone_numbers request failed: :message', [
                        'message' => $this->safeExceptionMessage($e),
                    ])
                ),
                'meta_phone_numbers' => [],
            ];
        }

        $metaPhoneNumbers = $this->normalizePhoneNumbers($response);

        if ($metaPhoneNumbers === []) {
            return [
                'check' => $this->makeCheck(
                    'phone_id_known_to_meta',
                    self::STATUS_WARN,
                    __('Phone number known to Meta'),
                    __('Meta returned no phone numbers for WABA :waba.', ['waba' => $wabaId])
                ),
                'meta_phone_numbers' => $metaPhoneNumbers,
            ];
        }

        if ($phoneId === '') {
            return [
                'check' => $this->makeCheck(
                    'phone_id_known_to_meta',
                    self::STATUS_FAIL,
                    __('Phone number known to Meta'),
                    __('No phone_id is stored locally, but Meta lists :count phone number(s).', [
                        'count' => (string) count($metaPhoneNumbers),
                    ])
                ),
                'meta_phone_numbers' => $metaPhoneNumbers,
            ];
        }

        foreach ($metaPhoneNumbers as $entry) {
            if (($entry['id'] ?? '') === $phoneId) {
                return [
                    'check' => $this->makeCheck(
                        'phone_id_known_to_meta',
                        self::STATUS_OK,
                        __('Phone number known to Meta'),
                        __('Stored phone_id matches a phone number returned by Meta.')
                    ),
                    'meta_phone_numbers' => $metaPhoneNumbers,
                ];
            }
        }

        return [
            'check' => $this->makeCheck(
                'phone_id_known_to_meta',
                self::STATUS_FAIL,
                __('Phone number known to Meta'),
                __('Stored phone_id :phone_id was not found among Meta phone numbers for WABA :waba.', [
                    'phone_id' => $phoneId,
                    'waba' => $wabaId,
                ])
            ),
            'meta_phone_numbers' => $metaPhoneNumbers,
        ];
    }

    /**
     * @param  array<string,mixed>  $response
     * @return array<int,array{id:string,display_phone_number:string,verified_name:string,quality_rating:string}>
     */
    private function normalizePhoneNumbers(array $response): array
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
                'display_phone_number' => (string) ($item['display_phone_number'] ?? ''),
                'verified_name' => (string) ($item['verified_name'] ?? ''),
                'quality_rating' => (string) ($item['quality_rating'] ?? ''),
            ];
        }

        return $normalized;
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
