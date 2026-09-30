<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Calling\Models\CallLog;
use App\Models\ApiCustomer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use RuntimeException;

final class CallingDemoDataForKkkkSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('username', 'kkkkk')->first();

        if ($user === null) {
            throw new RuntimeException('User kkkkk was not found.');
        }

        $customers = [];
        foreach ([
            ['Ahmed Alharbi', 'demo-calling-ahmed@example.test', '+966500001430'],
            ['Mona Alqahtani', 'demo-calling-mona@example.test', '+966500001431'],
            ['Khalid Alotaibi', 'demo-calling-khalid@example.test', '+966500001432'],
            ['Sara Alghamdi', 'demo-calling-sara@example.test', '+966500001433'],
        ] as [$name, $email, $phone]) {
            $customers[$phone] = ApiCustomer::query()->updateOrCreate(
                ['email' => $email],
                [
                    'user_id' => $user->id,
                    'name' => $name,
                    'phone_number' => $phone,
                    'password' => 'demo-calling-seed',
                ]
            );
        }

        foreach (array_values($customers) as $index => $customer) {
            $createdAt = Carbon::now()->subDays($index + 1)->setTime(10 + $index, 15);
            $id = sprintf('00000000-0000-4000-8000-%012d', 143000 + $index);

            CallLog::query()->updateOrCreate(
                ['id' => $id],
                [
                    'tenant_id' => $user->id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'direction' => $index % 2 === 0 ? 'outbound' : 'inbound',
                    'to_e164' => $customer->phone_number,
                    'from_e164' => $index % 2 === 0 ? '+966500001430' : $customer->phone_number,
                    'status' => 'completed',
                    'answered_at' => $createdAt->copy()->addSeconds(8),
                    'ended_at' => $createdAt->copy()->addSeconds(68 + $index),
                    'duration_seconds' => 60 + $index,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]
            );
        }

        if ($this->command !== null) {
            $this->command->info('Seeded repeatable calling demo data for user kkkkk.');
        }
    }
}
