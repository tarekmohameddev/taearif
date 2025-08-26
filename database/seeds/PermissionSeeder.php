<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder {
    public function run() {
      $guard = config('rbac.guard','sanctum');
      foreach (array_unique(config('rbac.permissions', [])) as $name) {
        Permission::findOrCreate($name, $guard); // global (no team_id)
      }
    }
  }
