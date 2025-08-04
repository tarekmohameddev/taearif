<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\{
    RmRental,
    RmReminder,
    RmContract,
    RmPaymentInstallment
};
use Illuminate\Support\Carbon;

class ReminderGeneratorService
{
    public function run()
    {
        $now = Carbon::now('Asia/Riyadh');
        $cutoff = $now->copy()->addDays(7);

        // 1. Payment Due
        RmPaymentInstallment::where('status', 'pending')
            ->whereBetween('due_date', [$now, $cutoff])
            ->get()
            ->each(function ($i) {
                $this->createUniqueReminder($i->user_id, [
                    'type' => 'payment_due',
                    'entity_type' => 'installment',
                    'entity_id' => $i->id,
                    'rental_id' => $i->rental_id,
                    'due_on' => $i->due_date,
                    'message' => 'Upcoming payment due',
                ]);
            });

        // 2. Payment Overdue
        RmPaymentInstallment::where('status', 'overdue')
            ->get()
            ->each(function ($i) {
                $this->createUniqueReminder($i->user_id, [
                    'type' => 'payment_overdue',
                    'entity_type' => 'installment',
                    'entity_id' => $i->id,
                    'rental_id' => $i->rental_id,
                    'due_on' => $i->due_date,
                    'message' => 'Payment is overdue',
                ]);
            });

        // 3. Contract Expiring
        RmContract::where('status', 'active')
            ->whereDate('end_date', '<=', $now->copy()->addDays(30))
            ->get()
            ->each(function ($c) {
                $this->createUniqueReminder($c->user_id, [
                    'type' => 'contract_expiring',
                    'entity_type' => 'contract',
                    'entity_id' => $c->id,
                    'rental_id' => $c->rental_id,
                    'due_on' => $c->end_date,
                    'message' => 'Contract ends in 30 days',
                ]);
            });
    }

    protected function createUniqueReminder($userId, $data)
    {
        RmReminder::updateOrCreate([
            'user_id' => $userId,
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'],
            'type' => $data['type'],
        ], [
            'rental_id' => $data['rental_id'],
            'due_on' => $data['due_on'],
            'message' => $data['message'],
            'status' => 'pending',
            'snooze_until' => null
        ]);
    }
}
