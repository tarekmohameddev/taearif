<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;
use App\Models\WaNumber;
use Illuminate\Console\Command;

final class DebugBotPropertySearch extends Command
{
    protected $signature = 'ai:debug-bot-property-search
                            {--tenant= : Tenant user_id (defaults to WaNumber.user_id)}
                            {--wa_number_id= : WaNumber id (defaults to first active)}
                            {--location= : Location text (e.g. "شارع الملك عبد العزيز")}
                            {--type=عمارة : Property type token}
                            {--budget_max=7000000 : Max budget}
                            {--purpose= : sale|rent}';

    protected $description = 'Debug the property search tool result ids for a given tenant and params.';

    public function handle(PropertySearchTool $tool): int
    {
        $waNumberId = (int) ($this->option('wa_number_id') ?: 0);
        if ($waNumberId <= 0) {
            $waNumberId = (int) (WaNumber::where('status', 'active')->orderBy('id')->value('id') ?: 0);
        }

        $waNumber = $waNumberId > 0 ? WaNumber::find($waNumberId) : null;
        if ($waNumber === null) {
            $this->error('No WaNumber found.');
            return self::FAILURE;
        }

        $tenantId = (int) ($this->option('tenant') ?: $waNumber->user_id);
        $params = [
            'location'      => (string) ($this->option('location') ?? ''),
            'property_type' => (string) ($this->option('type') ?? 'عمارة'),
            'budget_max'    => (float) ($this->option('budget_max') ?? 7_000_000),
        ];

        $purpose = (string) ($this->option('purpose') ?? '');
        if ($purpose !== '') {
            $params['purpose'] = $purpose;
        }

        $res = $tool->execute($tenantId, $params);
        $ids = array_map(fn ($r) => $r['id'] ?? null, $res['results'] ?? []);

        $this->line(json_encode([
            'tenant_id' => $tenantId,
            'wa_number_id' => $waNumberId,
            'params' => $params,
            'count' => $res['count'] ?? null,
            'ids' => $ids,
            'clarification_needed' => $res['clarification_needed'] ?? null,
            'clarification_question' => $res['clarification_question'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}

