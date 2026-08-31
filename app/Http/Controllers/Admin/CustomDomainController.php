<?php

namespace App\Http\Controllers\Admin;

use Session;
use Validator;
use App\Models\BasicSetting;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use App\Support\TenantActivity;
use App\Http\Helpers\MegaMailer;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiDomainSetting;
use App\Models\User\UserCustomDomain;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CustomDomainController extends Controller
{
    public function __construct(
        private readonly VercelDomainClient $vercel
    ) {
    }

    public function texts()
    {
        $data['abe'] = BasicExtended::select('domain_request_success_message', 'cname_record_section_title', 'cname_record_section_text')->first();
        return view('admin.domains.custom-texts', $data);
    }

    public function updateTexts(Request $request)
    {
        $rules = [
            'success_message' => 'required|max:255',
            'cname_record_section_title' => 'required|max:255',
            'cname_record_section_text' => 'required'
        ];
        $request->validate($rules);

        $be = BasicExtended::first();
        $be->domain_request_success_message = clean($request->success_message);
        $be->cname_record_section_title = $request->cname_record_section_title;
        $be->cname_record_section_text = clean($request->cname_record_section_text);
        $be->save();

        $request->session()->flash('success', 'Request Texts updated successfully');
        return back();
    }

    public function index(Request $request)
    {
        $rcDomains = ApiDomainSetting::with('user')->orderBy('id', 'DESC')
            ->when($request->domain, function ($query) use ($request) {
                $query->where('custom_name', 'LIKE', '%' . $request->domain . '%');
            })
            ->when($request->username, function ($query) use ($request) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('username', $request->username);
                });
            });

        if (empty($request->type)) {
            $rcDomains = $rcDomains->paginate(10);
        } elseif ($request->type == 'pending') {
            $rcDomains = $rcDomains->where('status', 'pending')->paginate(10);
        } elseif ($request->type == 'connected') {
            $rcDomains = $rcDomains->where('status', 'active')->paginate(10);
        } elseif ($request->type == 'rejected') {
            $rcDomains = $rcDomains->where('status', 'rejected')->paginate(10);
        } else {
            return view('errors.404');
        }

        $data['rcDomains'] = $rcDomains;
        $data['vercelCapacity'] = $this->resolveVercelCapacity();

        return view('admin.domains.custom', $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveVercelCapacity(): ?array
    {
        if (! $this->vercel->isConfigured()) {
            return null;
        }

        // Cache the upstream count only. A failure is cached briefly under the same
        // key as `false`, so an outage does not turn every page load into a timeout.
        $counted = Cache::remember('vercel.project_domain_count', now()->addMinutes(5), function () {
            try {
                return $this->vercel->countProjectDomains();
            } catch (\Throwable $e) {
                Log::warning('Vercel domain capacity unavailable', ['exception' => $e->getMessage()]);

                return false;
            }
        });

        if ($counted === false) {
            Cache::put('vercel.project_domain_count', false, now()->addSeconds(60));

            return null;
        }

        return $this->buildCapacity($counted);
    }

    /**
     * @param  array{count: int, is_lower_bound: bool}  $counted
     * @return array<string, mixed>
     */
    private function buildCapacity(array $counted): array
    {
        $entriesUsed = $counted['count'];
        $entriesTotal = (int) config('services.vercel.max_project_domains');
        $platformCount = (int) config('services.vercel.platform_domain_count');

        // Each customer domain consumes 2 Vercel entries (apex + www).
        $customerInUse = max(0, (int) floor(($entriesUsed - $platformCount) / 2));
        $customerRemaining = max(0, (int) floor(($entriesTotal - $entriesUsed) / 2));
        $usagePercent = $entriesTotal > 0 ? ($entriesUsed / $entriesTotal) * 100 : 0;

        $alertClass = $customerRemaining === 0 || $usagePercent >= 95
            ? 'danger'
            : ($usagePercent >= 80 ? 'warning' : 'success');

        return [
            'entries_used' => $entriesUsed,
            'entries_total' => $entriesTotal,
            'is_lower_bound' => $counted['is_lower_bound'],
            'customer_domains_in_use' => $customerInUse,
            'customer_domains_remaining' => $customerRemaining,
            'usage_percent' => $usagePercent,
            'alert_class' => $alertClass,
        ];
    }

    // public function index(Request $request)
    // {
    //     $rcDomains = UserCustomDomain::orderBy('id', 'DESC')
    //         ->when($request->domain, function ($query) use ($request) {
    //             return $query->where(function ($query) use ($request) {
    //                 $query->where('current_domain', 'LIKE', '%' . $request->domain . '%')
    //                     ->orWhere('requested_domain', 'LIKE', '%' . $request->domain . '%');
    //             });
    //         })
    //         ->when($request->username, function ($query) use ($request) {
    //             return $query->whereHas('user', function ($query) use ($request) {
    //                 $query->where('username', $request->username);
    //             });
    //         });
    //     if (empty($request->type)) {
    //         $rcDomains = $rcDomains->paginate(10);
    //     } elseif ($request->type == 'pending') {
    //         $rcDomains = $rcDomains->where('status', 0)->paginate(10);
    //     } elseif ($request->type == 'connected') {
    //         $rcDomains = $rcDomains->where('status', 1)->paginate(10);
    //     } elseif ($request->type == 'rejected') {
    //         $rcDomains = $rcDomains->where('status', 2)->paginate(10);
    //     } else {
    //         return view('errors.404');
    //     }
    //     $data['rcDomains'] = $rcDomains;
    //     dd($data);
    //     return view('admin.domains.custom', $data);
    // }
    public function updateSslStatus(Request $request)
    {
        $domain = ApiDomainSetting::findOrFail($request->domain_id);
        $domain->ssl = $request->status; // 1 = enabled, 0 = disabled
        $domain->save();


        TenantActivity::emit($request, 'domain.ssl_updated', 'api_domains_settings', $domain->id, ['old_ssl' => !$domain->ssl], ['new_ssl' => $domain->ssl]);

        $request->session()->flash('success', 'SSL status updated.');
        return back();
    }

    public function status(Request $request)
    {
        $rcDomain = ApiDomainSetting::findOrFail($request->domain_id);
        $rcDomain->status = $request->status;
        $rcDomain->save();

        // if the requested domain is connected
        if ($request->status == 1) {
            if (!empty($rcDomain->user)) {
                $user = $rcDomain->user;

                $bs = BasicSetting::firstOrFail();
                $mailer = new MegaMailer();
                $data = [
                    'toMail' => $user->email,
                    'toName' => $user->fname,
                    'username' => $user->username,
                    'requested_domain' => $rcDomain->requested_domain,
                    'previous_domain' => !empty($rcDomain->current_domain) ? $rcDomain->current_domain : 'Not Available',
                    'website_title' => $bs->website_title,
                    'templateType' => 'custom_domain_connected',
                    'type' => 'customDomainConnected'
                ];
                $mailer->mailFromAdmin($data);
            }
        } elseif ($request->status == 2) {
            if (!empty($rcDomain->user)) {
                $user = $rcDomain->user;
                $currDomCount = $user->custom_domains()->where('status', 1)->count();
                if ($currDomCount > 0) {
                    $currDom = $user->custom_domains()->where('status', 1)->orderBy('id', 'DESC')->first()->requested_domain;
                }

                $bs = BasicSetting::firstOrFail();
                $mailer = new MegaMailer();
                $data = [
                    'toMail' => $user->email,
                    'toName' => $user->fname,
                    'username' => $user->username,
                    'requested_domain' => $rcDomain->requested_domain,
                    'current_domain' => !empty($currDom) ? $currDom : 'Not Available',
                    'website_title' => $bs->website_title,
                    'templateType' => 'custom_domain_rejected',
                    'type' => 'customDomainRejected'
                ];
                $mailer->mailFromAdmin($data);
            }
        }

        $request->session()->flash('success', 'Status updated successfully');
        return back();
    }


    public function mail(Request $request)
    {
        $rules = [
            'email' => 'required',
            'subject' => 'required',
            'message' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        $be = BasicExtended::first();
        $from = $be->from_mail;

        $sub = $request->subject;
        $msg = $request->message;
        $to = $request->email;

        // Send Mail
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        if ($be->is_smtp == 1) {
            try {
                $mail->isSMTP();
                $mail->Host       = $be->smtp_host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $be->smtp_username;
                $mail->Password   = $be->smtp_password;
                $mail->SMTPSecure = $be->encryption;
                $mail->Port       = $be->smtp_port;

                //Recipients
                $mail->setFrom($from);
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $sub;
                $mail->Body    = $msg;

                $mail->send();
            } catch (\Exception $e) {
            }
        } else {
            try {

                //Recipients
                $mail->setFrom($from);
                $mail->addAddress($to);

                // Content
                $mail->isHTML(true);
                $mail->Subject = $sub;
                $mail->Body    = $msg;

                $mail->send();
            } catch (\Exception $e) {
            }
        }

        Session::flash('success', 'Mail sent successfully!');
        return "success";
    }

    public function delete(Request $request)
    {
        // index() lists ApiDomainSetting rows, so domain_id is an api_domains_settings id.
        $domain = ApiDomainSetting::findOrFail($request->domain_id);

        if (! $this->detachFromVercel($domain)) {
            $request->session()->flash('error', 'Could not remove the domain from the hosting provider. Nothing was deleted — please try again.');
            return redirect()->back();
        }

        $this->reassignPrimary($domain);
        $this->deleteAndRecord($request, $domain);

        $request->session()->flash('success', 'Custom domain deleted successfully!');
        return redirect()->back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = (array) $request->ids;

        // Resolve everything first: an unknown id aborts the whole batch before
        // anything is detached, rather than leaving it half applied.
        $domains = ApiDomainSetting::whereIn('id', $ids)->get();
        if ($domains->count() !== count(array_unique($ids))) {
            $request->session()->flash('error', 'Some selected domains no longer exist. Nothing was deleted — please refresh and try again.');
            return "error";
        }

        // Deliberately not wrapped in a transaction: Vercel removal cannot be
        // rolled back, so a DB rollback after a successful detach would leave
        // rows pointing at domains the project no longer serves. Each domain is
        // detached and deleted independently instead.
        $failed = [];
        foreach ($domains as $domain) {
            if (! $this->detachFromVercel($domain)) {
                $failed[] = $domain->custom_name;
                continue;
            }

            $this->reassignPrimary($domain);
            $this->deleteAndRecord($request, $domain);
        }

        if ($failed !== []) {
            $request->session()->flash('error', 'Some domains could not be removed from the hosting provider and were kept: ' . implode(', ', $failed));
            return "error";
        }

        $request->session()->flash('success', 'Custom domains deleted successfully!');
        return "success";
    }

    /**
     * Delete the row and record the activity, capturing its state beforehand.
     */
    private function deleteAndRecord(Request $request, ApiDomainSetting $domain): void
    {
        $before = $domain->only(['custom_name', 'status', 'primary', 'ssl']);
        $id = $domain->id;

        $domain->delete();

        TenantActivity::emit($request, 'domain.deleted', 'api_domains_settings', $id, $before, null);
    }

    /**
     * Detach apex + www from Vercel. Fails closed: returns false so the caller
     * keeps the row rather than orphaning the domain on the Vercel project.
     */
    private function detachFromVercel(ApiDomainSetting $domain): bool
    {
        if (! (bool) config('services.vercel.auto_attach_custom_domain', true) || ! $this->vercel->isConfigured()) {
            return true;
        }

        try {
            $this->vercel->removeApexAndWww((string) $domain->custom_name);
        } catch (VercelDomainException $e) {
            Log::warning('Failed to remove domain from Vercel during admin delete', [
                'domain_id' => $domain->id,
                'domain' => $domain->custom_name,
                'error' => $e->getMessage(),
                'status' => $e->statusCode,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Hand the primary flag to another active domain of the same tenant.
     *
     * Uses preferredActive() so the choice is deterministic and matches
     * DomainSettingsController::destroy(); an unordered first() picked an
     * arbitrary row when a tenant had several active domains.
     */
    private function reassignPrimary(ApiDomainSetting $domain): void
    {
        if (! $domain->primary) {
            return;
        }

        $another = ApiDomainSetting::where('user_id', $domain->user_id)
            ->where('id', '!=', $domain->id)
            ->preferredActive()
            ->first();

        if ($another) {
            $another->primary = true;
            $another->save();
        }
    }
}
