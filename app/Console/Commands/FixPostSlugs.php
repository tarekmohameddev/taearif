<?php

namespace App\Console\Commands;

use App\Models\Api\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixPostSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:fix-slugs
                            {--user-id= : Fix slugs for specific user only}
                            {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix invalid slugs (with spaces or special characters) in existing posts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->option('user-id');
        $dryRun = $this->option('dry-run');

        $query = Post::query();
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $posts = $query->get();
        $fixedCount = 0;
        $skippedCount = 0;

        $this->info("Found {$posts->count()} posts to check...");
        $this->newLine();

        foreach ($posts as $post) {
            $originalSlug = $post->slug;
            $sanitizedSlug = Str::slug($originalSlug);

            // Check if slug needs fixing (has spaces, uppercase, or special chars)
            if ($originalSlug !== $sanitizedSlug || $originalSlug !== strtolower($originalSlug)) {
                if ($dryRun) {
                    $this->line("Would fix: Post ID {$post->id} - '{$originalSlug}' → '{$sanitizedSlug}'");
                } else {
                    // Use the model's saving event to properly handle uniqueness
                    $post->slug = $sanitizedSlug;
                    $post->save();
                    // Refresh to get the final slug (may have been modified for uniqueness)
                    $post->refresh();
                    $this->line("✓ Fixed: Post ID {$post->id} - '{$originalSlug}' → '{$post->slug}'");
                }
                $fixedCount++;
            } else {
                $skippedCount++;
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info("Dry run complete. Would fix {$fixedCount} posts, skip {$skippedCount} posts.");
        } else {
            $this->info("✓ Fixed {$fixedCount} posts, skipped {$skippedCount} posts.");
        }

        return Command::SUCCESS;
    }
}
