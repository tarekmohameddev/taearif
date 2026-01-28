<?php

namespace App\Console\Commands;

use App\Models\Api\Category;
use Illuminate\Console\Command;

class FixCategorySlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'categories:fix-slugs
                            {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add slugs to existing categories that don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $categories = Category::whereNull('slug')->orWhere('slug', '')->get();
        
        if ($categories->isEmpty()) {
            $this->info('All categories already have slugs.');
            return Command::SUCCESS;
        }

        $this->info("Found {$categories->count()} categories without slugs...");
        $this->newLine();

        $fixedCount = 0;

        foreach ($categories as $category) {
            // The model's saving event will auto-generate the slug
            if ($dryRun) {
                $this->line("Would add slug to: Category ID {$category->id} - '{$category->name}'");
            } else {
                $category->save(); // Trigger slug generation
                $this->line("✓ Fixed: Category ID {$category->id} - '{$category->name}' → slug: '{$category->slug}'");
            }
            $fixedCount++;
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run complete. Would fix {$fixedCount} categories.");
        } else {
            $this->info("✓ Fixed {$fixedCount} categories.");
        }

        return Command::SUCCESS;
    }
}
