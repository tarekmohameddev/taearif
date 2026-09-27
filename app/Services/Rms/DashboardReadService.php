<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmMaintenanceTicket;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmRental;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardReadService
{
    private const ACTIVE_PAYMENT_STATUSES = ['pending', 'partial', 'overdue'];

    public function stats(int $ownerId): array
    {
        $now = Carbon::now('Asia/Riyadh');
        $month = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
        $next = [$now->copy()->addMonth()->startOfMonth(), $now->copy()->addMonth()->endOfMonth()];
        $year = [$now->copy()->startOfYear(), $now->copy()->endOfYear()];

        $properties = $this->rentalProperties($ownerId);
        $rented = (clone $properties)->whereHas('rentals', fn ($q) => $q->where('user_id', $ownerId)->where('status', 'active'))->count();
        $total = (clone $properties)->count();
        $monthly = $this->installmentTotals($ownerId, $month);
        $nextTotals = $this->installmentTotals($ownerId, $next);
        $overdue = $this->overdueQuery($ownerId, $now);

        return [
            'currency' => 'SAR',
            'counts' => [
                'ongoing_rentals' => RmRental::where('user_id', $ownerId)->where('status', 'active')->count(),
                'expiring_contracts_next_30d' => $this->expiringQuery($ownerId, 30, $now)->count(),
                'maintenance_open' => $this->maintenanceQuery($ownerId, 'open')->count(),
                'maintenance_in_progress' => $this->maintenanceQuery($ownerId, 'in_progress')->count(),
            ],
            'property_stats' => [
                'total_properties' => $total,
                'rented_properties' => $rented,
                'available_properties' => max($total - $rented, 0),
                'occupancy_rate' => $total ? round($rented / $total * 100, 2) : 0.0,
            ],
            'payments_due' => [
                'current_month' => ['total_amount' => $monthly['total'], 'paid_amount' => $monthly['paid'], 'unpaid_amount' => $monthly['remaining']],
                'next_month' => ['total_amount' => $nextTotals['total']],
            ],
            'overdue_payments' => [
                'total_overdue_count' => (clone $overdue)->count(),
                'total_overdue_amount' => $this->sumRemaining(clone $overdue),
                'current_month' => $this->overduePeriod($ownerId, $now, $month),
                'last_month' => $this->overduePeriod($ownerId, $now->copy()->subMonth()->startOfMonth(), [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()]),
                'yearly_overview' => $this->overduePeriod($ownerId, $now, $year),
            ],
        ];
    }

    public function ongoingRentals(int $ownerId, array $input): array
    {
        $page = (int) ($input['page'] ?? 1); $per = (int) ($input['per_page'] ?? 25);
        $paginator = RmRental::query()->where('user_id', $ownerId)->where('status', 'active')
            ->with(['property.contents', 'activeContract'])
            ->orderBy('id')->paginate($per, ['*'], 'page', $page);
        $ids = $paginator->getCollection()->pluck('id');
        $langs = $this->languageIds($ownerId);
        $payments = $this->nextPayments($ownerId, $ids, $langs);
        return $this->page($paginator, $paginator->getCollection()->map(fn ($r) => [
            'id' => $r->id, 'tenant_name' => $r->tenant_full_name, 'tenant_phone' => $r->tenant_phone,
            'property' => $this->propertyData($r->property, $langs),
            'contract' => $r->activeContract ? ['id'=>$r->activeContract->id,'status'=>$r->activeContract->status,'end_date'=>$this->date($r->activeContract->end_date)] : null,
            'next_payment' => $payments->get($r->id),
        ]));
    }

    public function paymentsDueSummary(int $ownerId): array
    {
        $now = Carbon::now('Asia/Riyadh'); $m = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]; $n = [$now->copy()->addMonth()->startOfMonth(), $now->copy()->addMonth()->endOfMonth()]; $y = [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
        $a = $this->installmentTotals($ownerId, $m); $b = $this->installmentTotals($ownerId, $n); $year = $this->yearTotals($ownerId, $y, $now);
        return ['currency'=>'SAR','this_month'=>['count'=>$a['count'],'total_amount'=>$a['total'],'paid_amount'=>$a['paid'],'unpaid_amount'=>$a['remaining']], 'next_month'=>['count'=>$b['count'],'total_amount'=>$b['total']], 'this_year'=>$year];
    }

    public function paymentsDueList(int $ownerId, array $input): array
    {
        $period = $input['period'] ?? 'this_month'; $page=(int)($input['page']??1); $per=(int)($input['per_page']??25); $now=Carbon::now('Asia/Riyadh'); $langs=$this->languageIds($ownerId);
        if ($period === 'this_year') {
            $range=[$now->copy()->startOfYear(),$now->copy()->endOfYear()];
            $q=$this->eligibleInstallments($ownerId)->whereBetween('rm_payment_installments.due_date',$range)->select('rm_payment_installments.contract_id')->selectRaw('MIN(rm_payment_installments.rental_id) rental_id')->selectRaw('SUM(rm_payment_installments.amount) total_expected')->selectRaw('SUM(LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount)) total_collected')->selectRaw("SUM(CASE WHEN rm_payment_installments.due_date >= ? AND rm_payment_installments.status IN ('pending','partial') THEN GREATEST(rm_payment_installments.amount-LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount),0) ELSE 0 END) total_pending", [$now->toDateString()])->selectRaw("SUM(CASE WHEN rm_payment_installments.due_date < ? AND rm_payment_installments.status IN ('pending','partial','overdue') THEN GREATEST(rm_payment_installments.amount-LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount),0) ELSE 0 END) total_overdue", [$now->toDateString()])->selectRaw('SUM(GREATEST(rm_payment_installments.amount-LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount),0)) total_outstanding')->groupBy('rm_payment_installments.contract_id');
            $p=$q->orderByDesc('contract_id')->paginate($per,['*'],'page',$page); $contracts=RmContract::whereIn('id',$p->getCollection()->pluck('contract_id'))->where('user_id',$ownerId)->with(['rental.property.contents'])->get()->keyBy('id'); $rentals=$contracts->pluck('rental')->filter()->keyBy('id');
            $items=$p->getCollection()->map(fn($x)=>['contract_id'=>(int)$x->contract_id,'rental_id'=>(int)$x->rental_id,'tenant_name'=>optional($rentals->get($x->rental_id))->tenant_full_name,'property'=>$this->propertyData(optional($rentals->get($x->rental_id))->property,$langs),'total_expected'=>$this->money($x->total_expected),'total_collected'=>$this->money($x->total_collected),'total_pending'=>$this->money($x->total_pending),'total_overdue'=>$this->money($x->total_overdue),'total_outstanding'=>$this->money($x->total_outstanding),'currency'=>'SAR','status'=>optional($contracts->get($x->contract_id))->status]);
            return ['period'=>$period,'items'=>$items->values(),'pagination'=>$this->pagination($p)];
        }
        $range=$period==='next_month'?[$now->copy()->addMonth()->startOfMonth(),$now->copy()->addMonth()->endOfMonth()]:[$now->copy()->startOfMonth(),$now->copy()->endOfMonth()];
        $p=$this->eligibleInstallments($ownerId)->whereBetween('rm_payment_installments.due_date',$range)->with(['rental.property.contents'])->orderBy('rm_payment_installments.due_date')->orderBy('rm_payment_installments.id')->paginate($per,['rm_payment_installments.*'],'page',$page);
        $items=$p->getCollection()->map(fn($i)=>$this->paymentItem($i,$ownerId,$langs,$now));
        return ['period'=>$period,'items'=>$items->values(),'pagination'=>$this->pagination($p)];
    }

    public function overduePaymentsSummary(int $ownerId): array
    {
        $now=Carbon::now('Asia/Riyadh'); $year=[$now->copy()->startOfYear(),$now->copy()->endOfYear()];
        return ['currency'=>'SAR','total_overdue_count'=>(clone $this->overdueQuery($ownerId,$now))->count(),'total_overdue_amount'=>$this->sumRemaining($this->overdueQuery($ownerId,$now)),'current_month'=>$this->overduePeriod($ownerId,$now,[$now->copy()->startOfMonth(),$now->copy()->subDay()]),'last_month'=>$this->overduePeriod($ownerId,$now->copy()->subMonth()->startOfMonth(),[$now->copy()->subMonth()->startOfMonth(),$now->copy()->subMonth()->endOfMonth()]),'this_year'=>$this->overduePeriod($ownerId,$now,$year)];
    }

    public function overduePayments(int $ownerId, array $input): array
    {
        $period=$input['period']??'current_month'; $page=(int)($input['page']??1); $per=(int)($input['per_page']??25); $now=Carbon::now('Asia/Riyadh'); $langs=$this->languageIds($ownerId);
        if ($period === 'last_month') {
            $range = [$now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()];
        } elseif ($period === 'this_year') {
            $range = [$now->copy()->startOfYear(), $now->copy()->subDay()];
        } else {
            $range = [$now->copy()->startOfMonth(), $now->copy()->subDay()];
        }
        $p=$this->overdueQuery($ownerId,$now)->whereBetween('rm_payment_installments.due_date',$range)->with(['rental.property.contents'])->orderBy('rm_payment_installments.due_date')->orderBy('rm_payment_installments.id')->paginate($per,['rm_payment_installments.*'],'page',$page);
        return ['period'=>$period,'items'=>$p->getCollection()->map(fn($i)=>$this->overdueItem($i,$ownerId,$langs,$now))->values(),'pagination'=>$this->pagination($p)];
    }

    public function expiringContracts(int $ownerId, array $input): array
    {
        $days=(int)($input['days']??30); $page=(int)($input['page']??1); $per=(int)($input['per_page']??25); $now=Carbon::now('Asia/Riyadh'); $langs=$this->languageIds($ownerId);
        $p=$this->expiringQuery($ownerId,$days,$now)->with(['rental.property.contents'])->orderBy('end_date')->orderBy('id')->paginate($per,['rm_contracts.*'],'page',$page);
        return ['items'=>$p->getCollection()->map(fn($c)=>['id'=>$c->id,'status'=>$c->status,'start_date'=>$this->date($c->start_date),'end_date'=>$this->date($c->end_date),'days_until_expiry'=>$now->startOfDay()->diffInDays(Carbon::parse($c->end_date,'Asia/Riyadh')->startOfDay()),'rental'=>$c->rental?['id'=>$c->rental->id,'tenant_name'=>$c->rental->tenant_full_name,'tenant_phone'=>$c->rental->tenant_phone,'property'=>$this->propertyData($c->rental->property,$langs)]:null])->values(),'pagination'=>$this->pagination($p)];
    }

    public function maintenance(int $ownerId, array $input): array
    {
        $page=(int)($input['page']??1); $per=(int)($input['per_page']??25); $status=$input['status']??'open';
        $p=$this->maintenanceQuery($ownerId,$status)->with(['rental'])->orderByDesc('created_at')->orderByDesc('id')->paginate($per,['rm_maintenance_tickets.*'],'page',$page);
        return ['items'=>$p->getCollection()->map(fn($t)=>['id'=>$t->id,'rental_id'=>$t->rental_id,'unit_id'=>$t->unit_id,'building_id'=>$t->building_id,'title'=>$t->title,'description'=>$t->description,'status'=>$t->status,'category'=>$t->category,'priority'=>$t->priority,'payer'=>$t->payer,'payer_share_percent'=>$t->payer_share_percent,'estimated_cost'=>$this->money($t->estimated_cost),'currency'=>'SAR','created_at'=>$this->date($t->created_at)])->values(),'pagination'=>$this->pagination($p)];
    }

    private function eligibleInstallments(int $ownerId): Builder
    {
        return RmPaymentInstallment::query()->join('rm_rentals','rm_rentals.id','=','rm_payment_installments.rental_id')->join('rm_contracts','rm_contracts.id','=','rm_payment_installments.contract_id')->where('rm_payment_installments.user_id',$ownerId)->where('rm_rentals.user_id',$ownerId)->where('rm_contracts.user_id',$ownerId)->whereNull('rm_rentals.deleted_at')->whereNull('rm_contracts.deleted_at')->whereNotIn('rm_payment_installments.status',['void','cancelled']);
    }
    private function overdueQuery(int $ownerId, Carbon $now): Builder { return $this->eligibleInstallments($ownerId)->whereIn('rm_payment_installments.status',self::ACTIVE_PAYMENT_STATUSES)->whereDate('rm_payment_installments.due_date','<',$now->toDateString())->whereRaw('GREATEST(rm_payment_installments.amount-LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount),0)>0')->from('rm_payment_installments'); }
    private function expiringQuery(int $ownerId,int $days,Carbon $now): Builder { return RmContract::query()->where('user_id',$ownerId)->where('status','active')->whereNull('deleted_at')->whereDate('end_date','>=',$now->toDateString())->whereDate('end_date','<=',$now->copy()->addDays($days)->toDateString()); }
    private function maintenanceQuery(int $ownerId,string $status): Builder { return RmMaintenanceTicket::query()->where('user_id',$ownerId)->where('status',$status)->whereNull('deleted_at')->where(function($q)use($ownerId){$q->whereNull('rental_id')->orWhereHas('rental',fn($r)=>$r->where('user_id',$ownerId)->whereNull('deleted_at'));})->where(function($q)use($ownerId){$q->whereNull('unit_id')->orWhereHas('unit',fn($u)=>$u->where('user_id',$ownerId));})->where(function($q)use($ownerId){$q->whereNull('building_id')->orWhereHas('building',fn($b)=>$b->where('user_id',$ownerId));}); }
    private function rentalProperties(int $ownerId): Builder { return Property::query()->where('user_id',$ownerId)->where(function($q){$q->where('listing_purpose','rent')->orWhere(function($q){$q->whereNull('listing_purpose')->where(function($q){$q->whereIn('purpose',['rent','rented'])->orWhere(function($q){$q->where(function($q){$q->whereNull('purpose')->orWhereNotIn('purpose',['sale','rent','sold','rented']);})->where('property_status','for_rent');});});});}); }
    private function installmentTotals(int $ownerId,array $range): array { $q=$this->eligibleInstallments($ownerId)->whereBetween('rm_payment_installments.due_date',$range); return ['count'=>(clone $q)->count(),'total'=>$this->money((clone $q)->sum('rm_payment_installments.amount')),'paid'=>$this->sumPaid($q),'remaining'=>$this->sumRemaining($q)]; }
    private function sumPaid(Builder $q): float { return $this->money($q->selectRaw('SUM(LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount)) x')->value('x')); }
    private function sumRemaining(Builder $q): float { return $this->money($q->selectRaw('SUM(GREATEST(rm_payment_installments.amount-LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount),0)) x')->value('x')); }
    private function overduePeriod(int $ownerId,Carbon $now,array $range): array { $q=$this->overdueQuery($ownerId,$now)->whereBetween('rm_payment_installments.due_date',$range); return ['count'=>(clone $q)->count(),'amount'=>$this->sumRemaining($q)]; }
    private function yearTotals(int $ownerId,array $range,Carbon $now): array { $q=$this->eligibleInstallments($ownerId)->whereBetween('rm_payment_installments.due_date',$range); $expected=$this->money((clone $q)->sum('rm_payment_installments.amount')); $collected=$this->sumPaid(clone $q); $over=$this->sumRemaining($this->overdueQuery($ownerId,$now)->whereBetween('rm_payment_installments.due_date',$range)); $pending=$this->sumRemaining((clone $q)->whereIn('rm_payment_installments.status',['pending','partial'])->whereDate('rm_payment_installments.due_date','>=',$now->toDateString())); $contracts=RmContract::where('user_id',$ownerId)->whereIn('status',['pending','active','expired','terminated'])->whereHas('installments',fn($i)=>$i->whereBetween('due_date',$range)->whereNotIn('status',['void','cancelled']))->selectRaw('status, COUNT(*) count')->groupBy('status')->pluck('count','status'); return ['total_contracts'=>(int)$contracts->sum(),'pending_contracts'=>(int)($contracts['pending']??0),'active_contracts'=>(int)($contracts['active']??0),'expired_contracts'=>(int)($contracts['expired']??0),'terminated_contracts'=>(int)($contracts['terminated']??0),'total_expected'=>$expected,'total_collected'=>$collected,'total_pending'=>$pending,'total_overdue'=>$over,'total_outstanding'=>$this->money($pending+$over),'collection_rate'=>$expected?round($collected/$expected*100,2):0.0]; }
    private function nextPayments(int $ownerId,$ids,$langs) { $q=$this->eligibleInstallments($ownerId)->whereIn('rm_payment_installments.rental_id',$ids)->whereIn('rm_payment_installments.status',self::ACTIVE_PAYMENT_STATUSES)->whereRaw('GREATEST(rm_payment_installments.amount-LEAST(GREATEST(COALESCE(rm_payment_installments.paid_amount,0),0),rm_payment_installments.amount),0)>0')->orderBy('rm_payment_installments.due_date')->orderBy('rm_payment_installments.id')->with(['rental.property.contents'])->get(['rm_payment_installments.*']); return $q->groupBy('rental_id')->map(fn($x)=>$this->paymentItem($x->first(),$ownerId,$langs,Carbon::now('Asia/Riyadh'))); }
    private function paymentItem($i,int $ownerId,$langs,Carbon $now): array { $r=$i->rental?:$this->rental($i->rental_id,$ownerId); return ['installment_id'=>$i->id,'rental_id'=>$i->rental_id,'contract_id'=>$i->contract_id,'tenant_name'=>optional($r)->tenant_full_name,'tenant_phone'=>optional($r)->tenant_phone,'property'=>$this->propertyData(optional($r)->property,$langs),'days_remaining'=>max(0,$now->startOfDay()->diffInDays(Carbon::parse($i->due_date,'Asia/Riyadh')->startOfDay(),false)),'payment_details'=>['amount'=>$this->money($i->amount),'paid_amount'=>$this->money($this->paid($i)),'remaining_amount'=>$this->money($this->remaining($i)),'due_date'=>$this->date($i->due_date),'status'=>$i->status,'currency'=>'SAR']]; }
    private function overdueItem($i,int $ownerId,$langs,Carbon $now): array { $r=$i->rental?:$this->rental($i->rental_id,$ownerId); return ['installment_id'=>$i->id,'rental_id'=>$i->rental_id,'contract_id'=>$i->contract_id,'tenant_name'=>optional($r)->tenant_full_name,'tenant_phone'=>optional($r)->tenant_phone,'property'=>$this->propertyData(optional($r)->property,$langs),'amount'=>$this->money($i->amount),'paid_amount'=>$this->money($this->paid($i)),'remaining_amount'=>$this->money($this->remaining($i)),'due_date'=>$this->date($i->due_date),'days_overdue'=>Carbon::parse($i->due_date,'Asia/Riyadh')->startOfDay()->diffInDays($now->startOfDay()),'status'=>$i->status,'currency'=>'SAR']; }
    private function rental($id,int $ownerId){return RmRental::where('id',$id)->where('user_id',$ownerId)->whereNull('deleted_at')->with('property.contents')->first();}
    private function paid($i): float {return min(max((float)($i->paid_amount??0),0),(float)$i->amount);}
    private function remaining($i): float {return max((float)$i->amount-$this->paid($i),0);}
    private function languageIds(int $ownerId){return Language::where('user_id',$ownerId)->where('is_default',1)->orderBy('id')->pluck('id')->values();}
    private function propertyData($p,$langs): array { if(!$p)return ['id'=>null,'name'=>null]; $c=$p->contents->sortBy('id'); $preferred=null; foreach($langs as $langId){$preferred=$c->firstWhere('language_id',$langId); if($preferred)break;} return ['id'=>$p->id,'name'=>optional($preferred?:$c->first())->title]; }
    private function date($d): ?string {return $d?Carbon::parse($d)->format('Y-m-d'):null;}
    private function money($v): float {return round((float)$v,2);}
    private function pagination($p): array {return ['page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'has_more'=>$p->currentPage()<$p->lastPage()];}
    private function page($p,$items): array {return ['items'=>$items->values(),'pagination'=>$this->pagination($p)];}
}
