<?php

declare(strict_types=1);

namespace App\Domain\CRM\Pipedrive\Services;

use App\Domain\CRM\Pipedrive\DTOs\PipedriveCredentialsDto;
use App\Models\BasicSetting;

class PipedriveSettingsService
{
    private const TOKEN_MASK = '********';

    public function getCredentials(): PipedriveCredentialsDto
    {
        $bs = $this->loadBasicSetting();

        return new PipedriveCredentialsDto(
            enabled: (bool) ($bs?->pipedrive_sync_enabled ?? false),
            apiToken: $bs?->pipedrive_api_token ?: null,
            baseUrl: $bs?->pipedrive_base_url ? rtrim($bs->pipedrive_base_url, '/') : null,
            pipelineId: $bs?->pipedrive_pipeline_id ? (int) $bs->pipedrive_pipeline_id : null,
            stageId: $bs?->pipedrive_stage_id ? (int) $bs->pipedrive_stage_id : null,
            dealTitlePrefix: $bs?->pipedrive_deal_title_prefix ?: null,
        );
    }

    /**
     * Return settings for API response — masks the API token.
     */
    public function getSettingsForApi(): array
    {
        $creds = $this->getCredentials();

        return [
            'enabled' => $creds->enabled,
            'api_token' => $creds->apiToken ? self::TOKEN_MASK : null,
            'base_url' => $creds->baseUrl,
            'pipeline_id' => $creds->pipelineId,
            'stage_id' => $creds->stageId,
            'deal_title_prefix' => $creds->dealTitlePrefix,
        ];
    }

    /**
     * Overridable in tests to inject a fake model without hitting the DB.
     */
    protected function loadBasicSetting(): ?BasicSetting
    {
        return BasicSetting::first();
    }

    /**
     * Persist Pipedrive settings. Skips token update when mask value is passed.
     */
    public function updateSettings(array $data): array
    {
        $bss = BasicSetting::all();

        foreach ($bss as $bs) {
            if (array_key_exists('enabled', $data)) {
                $wantsEnabled = (bool) $data['enabled'];

                if ($wantsEnabled) {
                    $hasToken = (isset($data['api_token']) && $data['api_token'] !== self::TOKEN_MASK && $data['api_token'] !== '')
                        || $bs->pipedrive_api_token;
                    $hasUrl = (isset($data['base_url']) && $data['base_url'] !== '')
                        || $bs->pipedrive_base_url;

                    if ($hasToken && $hasUrl) {
                        $bs->pipedrive_sync_enabled = true;
                    }
                    // silently skip enabling if credentials are missing
                } else {
                    $bs->pipedrive_sync_enabled = false;
                }
            }

            if (isset($data['api_token']) && $data['api_token'] !== self::TOKEN_MASK && $data['api_token'] !== '') {
                $bs->pipedrive_api_token = $data['api_token'];
            }

            if (array_key_exists('base_url', $data) && $data['base_url'] !== null) {
                $bs->pipedrive_base_url = rtrim((string) $data['base_url'], '/');
            }

            if (array_key_exists('pipeline_id', $data)) {
                $bs->pipedrive_pipeline_id = $data['pipeline_id'] ? (int) $data['pipeline_id'] : null;
            }

            if (array_key_exists('stage_id', $data)) {
                $bs->pipedrive_stage_id = $data['stage_id'] ? (int) $data['stage_id'] : null;
            }

            if (array_key_exists('deal_title_prefix', $data)) {
                $bs->pipedrive_deal_title_prefix = $data['deal_title_prefix'] ?: null;
            }

            $bs->save();
        }

        return $this->getSettingsForApi();
    }
}
