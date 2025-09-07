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

        // Free package id (ID: 16 - الباقة-المجانية)
        $freePackageId = 16; // الباقة-المجانية
        $freePackage = Package::find($freePackageId);
        
        if (! $freePackage || $freePackage->status != '1') {
            $this->error('Free package (ID: 16) not found or inactive.');
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
                        
                        // Ensure user has language data (safety check)
                        if ($user->languages()->count() == 0) {
                            $deLang = \App\Models\User\Language::where('user_id', 0)->first();
                            if ($deLang) {
                                $lang = new \App\Models\User\Language;
                                $lang->name = $deLang->name;
                                $lang->code = $deLang->code;
                                $lang->is_default = 1;
                                $lang->rtl = $deLang->rtl;
                                $lang->user_id = $user->id;
                                $lang->keywords = $deLang->keywords;
                                $lang->save();
                                $this->info("Created missing language for user: {$user->username}");
                            }
                        }
                        
                        // Set welcome message for user to see in dashboard
                        $user->message = 'تم تحويلك إلى الباقة المجانية بعد انتهاء فترة التجربة. يمكنك ترقية باقاتك في أي وقت من لوحة التحكم.';
                        $user->save();
                        
                        // Send notification that user was switched to free package
                        // $bs = BasicSetting::first();
                        // $be = BasicExtended::first();
                        // \App\Jobs\FreePackageSwitchMail::dispatch($user, $bs, $be);
                    }
                }
            });

        $this->info('Expired users downgraded to free.');
        return self::SUCCESS;
    }

}
