<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\WaAiConfig;

class WhatsAppAiConfigService
{
    public function findForNumber(int $userId, int $waNumberId): ?WaAiConfig
    {
        return WaAiConfig::query()
            ->with('excludedPhones')
            ->where('user_id', $userId)
            ->where('wa_number_id', $waNumberId)
            ->first();
    }

    public function createOrUpdate(int $userId, int $waNumberId, array $data): WaAiConfig
    {
        $data['user_id'] = $userId;
        $data['wa_number_id'] = $waNumberId;

        // `scenarios` is NOT NULL with no DB default; clients often omit it on first save.
        $exists = WaAiConfig::query()
            ->where('user_id', $userId)
            ->where('wa_number_id', $waNumberId)
            ->exists();

        if (! $exists && ! array_key_exists('scenarios', $data)) {
            $data['scenarios'] = [];
        }

        return WaAiConfig::updateOrCreate(
            ['user_id' => $userId, 'wa_number_id' => $waNumberId],
            $data
        );
    }

    public function toggle(WaAiConfig $config): WaAiConfig
    {
        $config->update(['enabled' => ! $config->enabled]);

        return $config->refresh();
    }

    public function statsForUser(int $userId): array
    {
        return WaAiConfig::query()
            ->where('user_id', $userId)
            ->get()
            ->all();
    }
}
