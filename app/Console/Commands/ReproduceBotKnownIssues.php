<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Communication\WhatsApp\Bot\SandboxService;
use App\Models\WaNumber;
use Illuminate\Console\Command;

final class ReproduceBotKnownIssues extends Command
{
    protected $signature = 'ai:reproduce-known-issues
                            {--wa_number_id= : WhatsApp number id (defaults to first active)}
                            {--customer_phone=+966500000099 : Sandbox customer phone}
                            {--reset : Reset sandbox conversation first (default true)}';

    protected $description = 'Reproduce the known-issues sandbox conversation and print the bot facts snapshot.';

    public function handle(SandboxService $sandbox): int
    {
        $waNumberId = (int) ($this->option('wa_number_id') ?: 0);
        if ($waNumberId <= 0) {
            $waNumberId = (int) (WaNumber::where('status', 'active')->orderBy('id')->value('id') ?: 0);
        }

        if ($waNumberId <= 0) {
            $this->error('No active WaNumber found.');
            return self::FAILURE;
        }

        $waNumber = WaNumber::find($waNumberId);
        if ($waNumber === null) {
            $this->error("WaNumber {$waNumberId} not found.");
            return self::FAILURE;
        }

        $tenantId = (int) $waNumber->user_id;
        $phone    = (string) $this->option('customer_phone');

        if ($phone === '') {
            $this->error('customer_phone is required.');
            return self::FAILURE;
        }

        $shouldReset = (bool) ($this->option('reset') ?? true);
        if ($shouldReset) {
            $sandbox->reset($tenantId, $waNumberId, $phone);
        }

        $turn1 = $sandbox->runTurn(
            tenantId: $tenantId,
            waNumberId: $waNumberId,
            phone: $phone,
            messageText: 'حياك الله',
        );

        $turn2 = $sandbox->runTurn(
            tenantId: $tenantId,
            waNumberId: $waNumberId,
            phone: $phone,
            messageText: 'بدور على عمارة في شارع الملك عبد العزيز بميزانية 7 مليون',
        );

        $payload = [
            'wa_number_id' => $waNumberId,
            'tenant_id'    => $tenantId,
            'customer_phone' => $phone,
            'turn1' => [
                'outcome'       => $turn1['outcome'] ?? null,
                'reply'         => $turn1['reply'] ?? null,
                'failed_turns'  => $turn1['facts']['_failed_turns'] ?? null,
            ],
            'turn2' => [
                'outcome'       => $turn2['outcome'] ?? null,
                'reply'         => $turn2['reply'] ?? null,
                'next_question' => $turn2['next_question'] ?? null,
                'type'          => $turn2['facts']['type'] ?? null,
                'district'      => $turn2['facts']['district'] ?? null,
                'failed_turns'  => $turn2['facts']['_failed_turns'] ?? null,
            ],
        ];

        $this->line(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}

