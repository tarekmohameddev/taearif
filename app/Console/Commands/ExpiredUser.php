<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\BasicSetting;
use App\Models\BasicExtended;
use App\Models\Package;
use App\Services\UserPackageService;
use App\Services\WhatsAppService;
use App\Services\MembershipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
class ExpiredUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expire:user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downgrade users with expired memberships to the free package';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(MembershipService $membershipService): int
    {
        // Timezone
        if ($bs = BasicSetting::first()) {
            Config::set('app.timezone', $bs->timezone);
        }

        // Check if free package exists
        $freePackage = $membershipService->getFreePackage();
        
        if (! $freePackage || $freePackage->status != '1') {
            $this->error('Free package (ID: ' . MembershipService::FREE_PACKAGE_ID . ') not found or inactive.');
            return self::FAILURE;
        }

        // Get users whose current membership already expired
        $expiredUserIds = Membership::query()
            ->where('status', 1)
            ->whereDate('expire_date', '<', now()->toDateString())
            ->pluck('user_id')
            ->unique();

        if ($expiredUserIds->isEmpty()) {
            $this->info('No users to downgrade.');
            return self::SUCCESS;
        }

        $expiredCount = 0;
        
        // For each expired user: if no active package NOW, switch to free
        User::whereIn('id', $expiredUserIds)
            ->chunkById(200, function ($users) use ($membershipService, &$expiredCount) {
                foreach ($users as $user) {
                    if (is_null(UserPermissionHelper::userPackage($user->id))) {
                        // Use the MembershipService to handle the entire process
                        $membershipService->handleMembershipExpiration($user);
                        $expiredCount++;
                        
                        $this->info("Processed user: {$user->username} (ID: {$user->id})");
                    }
                }
            });

        $this->info('Expired users downgraded to free package with maintenance mode enabled.');
        $this->info("Summary: {$expiredCount} users processed.");
        return self::SUCCESS;
    }

}
