<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsAppTemplate;
use Illuminate\Support\Facades\DB;

class MigrateWhatsAppTemplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'templates:migrate-whatsapp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate WhatsApp templates from shared table to dedicated table';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting WhatsApp templates migration...');

        try {
            // Check if the old table exists
            if (!DB::getSchemaBuilder()->hasTable('whats_app_templates')) {
                $this->warn('Old whats_app_templates table does not exist. Nothing to migrate.');
                return Command::SUCCESS;
            }

            // Get WhatsApp templates from old table
            $oldTemplates = DB::table('whats_app_templates')
                ->where('channel', 'whatsapp')
                ->orWhereNull('channel') // Include templates without channel (legacy)
                ->get();

            if ($oldTemplates->isEmpty()) {
                $this->info('No WhatsApp templates found to migrate.');
                return Command::SUCCESS;
            }

            $this->info("Found {$oldTemplates->count()} WhatsApp templates to migrate.");

            foreach ($oldTemplates as $oldTemplate) {
                try {
                    // Check if template already exists in new table
                    $exists = WhatsAppTemplate::where('name', $oldTemplate->name)->exists();
                    
                    if ($exists) {
                        $this->warn("Template '{$oldTemplate->name}' already exists, skipping...");
                        continue;
                    }

                    // Create new template
                    WhatsAppTemplate::create([
                        'name' => $oldTemplate->name,
                        'description' => $oldTemplate->description,
                        'content' => $oldTemplate->content,
                        'type' => $oldTemplate->type,
                        'language' => $oldTemplate->language ?? 'ar',
                        'variables' => $oldTemplate->variables ? json_decode($oldTemplate->variables, true) : null,
                        'status' => $oldTemplate->status ?? true,
                        'character_count' => $oldTemplate->character_count ?? strlen($oldTemplate->content),
                        'created_at' => $oldTemplate->created_at,
                        'updated_at' => $oldTemplate->updated_at
                    ]);

                    $this->info("Migrated template: {$oldTemplate->name}");
                } catch (\Exception $e) {
                    $this->error("Failed to migrate template '{$oldTemplate->name}': " . $e->getMessage());
                }
            }

            $this->info('WhatsApp templates migration completed successfully!');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
