<?php

namespace App\Observers;

use App\Models\Api\Crm\CrmCard;
use App\Models\Logs\CardLog;
use App\Support\AuditContext;

class CrmCardObserver
{
    public function created(CrmCard $card): void
    {
        CardLog::create(array_merge(AuditContext::data(), [
            'tenant_id' => (int) $card->user_id,
            'card_id'   => (int) $card->id,
            'action'    => 'created',
            'changes'   => ['after' => $card->getAttributes()],
        ]));
    }

    public function updated(CrmCard $card): void
    {
        CardLog::create(array_merge(AuditContext::data(), [
            'tenant_id' => (int) $card->user_id,
            'card_id'   => (int) $card->id,
            'action'    => 'updated',
            'changes'   => [
                'before' => $card->getOriginal(),
                'after'  => $card->getAttributes(),
            ],
        ]));
    }

    public function deleted(CrmCard $card): void
    {
        CardLog::create(array_merge(AuditContext::data(), [
            'tenant_id' => (int) $card->user_id,
            'card_id'   => (int) $card->id,
            'action'    => 'deleted',
            'changes'   => ['before' => $card->getOriginal()],
        ]));
    }
}
