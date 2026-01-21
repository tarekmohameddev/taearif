<?php

namespace Database\Seeders;

use App\Models\User\JobApplication;
use Illuminate\Database\Seeder;

class JobApplicationFakeSeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1430;

        $rows = [
            ['name' => 'Ahmed Ali', 'phone' => '+966501234567', 'email' => 'ahmed.ali@example.com', 'description' => '5 years Laravel experience. Looking for senior role.'],
            ['name' => 'Sara Mohammed', 'phone' => '+966552987654', 'email' => 'sara.m@example.com', 'description' => 'Frontend developer, 3 years React/Vue.'],
            ['name' => 'Omar Hassan', 'phone' => '+966541112233', 'email' => 'omar.hassan@example.com', 'description' => 'Full stack, PHP and Node.js.'],
            ['name' => 'Fatima Ibrahim', 'phone' => '+966577665544', 'email' => 'fatima.i@example.com', 'description' => 'UI/UX designer with development skills.'],
            ['name' => 'Khalid Yousef', 'phone' => '+966533344455', 'email' => 'khalid.y@example.com', 'description' => 'Backend specialist, Laravel and PostgreSQL.'],
            ['name' => 'Noor Abdullah', 'phone' => '+966566677788', 'email' => 'noor.abdullah@example.com', 'description' => 'Junior developer, eager to learn.'],
            ['name' => 'Mohammed Rashid', 'phone' => '+966599988877', 'email' => 'm.rashid@example.com', 'description' => 'DevOps and cloud, 4 years AWS.'],
            ['name' => 'Layla Mahmoud', 'phone' => '+966512345678', 'email' => 'layla.m@example.com', 'description' => 'Mobile developer, Flutter and React Native.'],
        ];

        foreach ($rows as $row) {
            JobApplication::create([
                'user_id' => $userId,
                'name' => $row['name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'description' => $row['description'],
                'pdf_path' => 'job_applications/fake-' . \Illuminate\Support\Str::slug($row['name']) . '.pdf',
            ]);
        }

        $this->command->info('Inserted ' . count($rows) . ' fake job applications for user_id ' . $userId . ' (username: kkkkk).');
    }
}
