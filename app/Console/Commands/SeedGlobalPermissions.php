<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class SeedGlobalPermissions extends Command
{
	protected $signature = 'rbac:seed-global-permissions {--guard=sanctum}';
	protected $description = 'Seed global (team_id NULL) permissions from config/rbac.php';

	public function handle(): int
	{
		$guard = (string) $this->option('guard');
		$names = collect(config('rbac.permissions', []))->filter()->unique()->values();
		if ($names->isEmpty()) {
			$this->info('No permissions found in config/rbac.php');
			return self::SUCCESS;
		}

		$created = 0; $skipped = 0;
		foreach ($names as $name) {
			$existsGlobal = Permission::query()
				->where('name', $name)
				->where('guard_name', $guard)
				->whereNull('team_id')
				->first();
			if ($existsGlobal) {
				$skipped++;
				continue;
			}

			Permission::create([
				'name' => $name,
				'guard_name' => $guard,
				'team_id' => null,
			]);
			$created++;
		}

		$this->info("Global permissions seeded. Created: {$created}, Skipped: {$skipped}.");
		return self::SUCCESS;
	}
} 