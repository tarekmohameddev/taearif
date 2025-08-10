<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\BasicSetting;
use App\Models\Package;
use App\Services\UserPackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
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
    public function handle(UserPackageService $service): int
    {
        // Timezone
        if ($bs = BasicSetting::first()) {
            Config::set('app.timezone', $bs->timezone);
        }

        // Free package id (price = 0 and active)
        $freePackageId = Package::query()
            ->where('status', '1')
            ->where('price', 0)
            ->value('id');

        if (! $freePackageId) {
            $this->error('No active free package found (price=0).');
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

        // For each expired user: if no active package NOW, switch to free
        User::whereIn('id', $expiredUserIds)
            ->chunkById(200, function ($users) use ($service, $freePackageId) {
                foreach ($users as $user) {
                    if (is_null(UserPermissionHelper::userPackage($user->id))) {
                        $req = new Request([
                            'user_id'        => $user->id,
                            'package_id'     => $freePackageId,
                            'payment_method' => 'system',
                        ]);
                        $service->addCurrentPackage($req);
                        // dispatch SubscriptionExpiredMail here
                    }
                }
            });

        $this->info('Expired users downgraded to free.');
        return self::SUCCESS;
    }

}
