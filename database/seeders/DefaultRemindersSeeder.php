<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Api\UserApiCustomerReminder;

class DefaultRemindersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $defaultReminders = [
            [
                'title' => 'مكالمه',
                'priority' => null,
            ],
            [
                'title' => 'زيارة مكتب',
                'priority' => null,
            ],
            [
                'title' => 'معاينة موقع',
                'priority' => null,
            ],
        ];

        foreach ($defaultReminders as $reminder) {
            UserApiCustomerReminder::updateOrCreate(
                [
                    'user_id' => null,
                    'title' => $reminder['title'],
                ],
                [
                    'customer_id' => null,
                    'priority' => $reminder['priority'],
                    'datetime' => now(),
                ]
            );
        }
    }
}

