<?php

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Repositories\InvoiceRepositoryInterface;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Domain\Billing\Models\Plan;
use App\Domain\User\Models\User;
use App\Services\MembershipService;

/**
 * Invoice Service
 *
 * Handles invoice/billing business logic including
 * approval workflows, payment processing, and revenue tracking
 */
class InvoiceService extends BaseService
{
    /**
     * @var InvoiceRepositoryInterface
     */
    protected InvoiceRepositoryInterface $invoiceRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected UserRepositoryInterface $userRepository;

    /**
     * InvoiceService constructor.
     *
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param UserRepositoryInterface $userRepository
     */
    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        UserRepositoryInterface $userRepository
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Get all invoices with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllInvoices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->invoiceRepository->getInvoices($filters, $perPage);
    }

    /**
     * Get invoice by ID
     *
     * @param int $id
     * @return Invoice
     * @throws ResourceNotFoundException
     */
    public function getInvoiceById(int $id): Invoice
    {
        $invoice = $this->invoiceRepository->findById($id);

        if (!$invoice) {
            throw new ResourceNotFoundException('Invoice not found');
        }

        return $invoice->load(['user', 'package']);
    }

    /**
     * Get latest invoice for a user.
     *
     * @param int $userId
     * @return Invoice
     * @throws ResourceNotFoundException
     */
    public function getInvoiceByUserId(int $userId): Invoice
    {
        $invoice = $this->invoiceRepository->findLatestForUser($userId);

        if (!$invoice) {
            throw new ResourceNotFoundException('Invoice not found for this user');
        }

        return $invoice->load(['user', 'package']);
    }

    /**
     * Get invoice statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $invoiceStats = $this->invoiceRepository->getStatistics();
        $revenueStats = $this->invoiceRepository->getRevenueStatistics();

        return [
            'invoices' => $invoiceStats,
            'revenue' => $revenueStats,
        ];
    }

    /**
     * Get revenue for date range
     *
     * @param string $from
     * @param string $to
     * @return array
     */
    public function getRevenue(string $from, string $to): array
    {
        $revenue = $this->invoiceRepository->getRevenue($from, $to);
        
        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'revenue' => $revenue,
            'formatted' => '$' . number_format($revenue, 2),
        ];
    }

    /**
     * Approve invoice and activate subscription
     *
     * @param int $id
     * @param array $data
     * @return Invoice
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function approveInvoice(int $id, array $data = []): Invoice
    {
        $invoice = $this->getInvoiceById($id);

        // Validate invoice can be approved
        if ($invoice->isPaid()) {
            throw new BusinessLogicException('Invoice is already approved', 'INVOICE_ALREADY_APPROVED', 400);
        }

        if ($invoice->isRejected()) {
            throw new BusinessLogicException('Rejected invoices cannot be approved', 'INVOICE_REJECTED', 400);
        }

        return $this->executeInTransaction(function () use ($invoice, $data) {
            // Get related data
            $user = $invoice->user;
            $package = $invoice->package;
            $userInvoiceCount = $this->invoiceRepository->getUserInvoiceCount($user->id);

            // Calculate start and expire dates
            $dates = $this->calculateSubscriptionDates($invoice, $package);

            // Update invoice with new dates and status
            $invoice->update([
                'status' => 1,
                'start_date' => $dates['start_date'],
                'expire_date' => $dates['expire_date'],
            ]);

            // Expire any other active/pending memberships for this user
            app(MembershipService::class)->expireActiveMemberships($user->id, $invoice->id);

            // Activate user account if first purchase
            if ($userInvoiceCount <= 1) {
                $user->update(['status' => 1]);
            }

            app(MembershipService::class)->applyPackageTransitionHooks($user, $package->id, 'invoice_approval');

            // Queue email notification
            $this->queueApprovalEmail($invoice, $user, $package, $dates, $userInvoiceCount);

            return $invoice->fresh(['user', 'package']);
        });
    }

    /**
     * Reject invoice
     *
     * @param int $id
     * @param array $data
     * @return Invoice
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function rejectInvoice(int $id, array $data = []): Invoice
    {
        $invoice = $this->getInvoiceById($id);

        // Validate invoice can be rejected
        if ($invoice->isPaid()) {
            throw new BusinessLogicException('Paid invoices cannot be rejected', 'INVOICE_ALREADY_PAID', 400);
        }

        if ($invoice->isRejected()) {
            throw new BusinessLogicException('Invoice is already rejected', 'INVOICE_ALREADY_REJECTED', 400);
        }

        $reason = $data['reason'] ?? null;

        return $this->executeInTransaction(function () use ($invoice, $reason) {
            // Update invoice status
            $invoice->update(['status' => 2]);

            // Get related data
            $user = $invoice->user;
            $package = $invoice->package;
            $userInvoiceCount = $this->invoiceRepository->getUserInvoiceCount($user->id);

            // Queue email notification
            $this->queueRejectionEmail($invoice, $user, $package, $reason, $userInvoiceCount);

            return $invoice->fresh(['user', 'package']);
        });
    }

    /**
     * Calculate subscription start and expire dates
     *
     * @param Invoice $invoice
     * @param Package $package
     * @return array
     */
    protected function calculateSubscriptionDates(Invoice $invoice, Plan $package): array
    {
        // Check if invoice start date is in the future
        $invoiceStartDate = Carbon::parse($invoice->start_date);
        $today = Carbon::today();

        if ($invoiceStartDate->gte($today)) {
            // Use invoice dates if start is today or future
            return [
                'start_date' => $invoice->start_date,
                'expire_date' => $invoice->expire_date,
            ];
        }

        // Calculate new dates based on today
        $startDate = Carbon::today();
        $expireDate = $this->calculateExpireDate($startDate, $package->term, $package->trial_days);

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'expire_date' => $expireDate->format('Y-m-d'),
        ];
    }

    /**
     * Calculate expire date based on package term
     *
     * @param Carbon $startDate
     * @param string $term
     * @return Carbon
     */
    protected function calculateExpireDate(Carbon $startDate, string $term, $trialDays = null): Carbon
    {
        if ($term === 'trial') {
            $days = (int) $trialDays;
            if ($days < 1) {
                $days = MembershipService::DEFAULT_TRIAL_DAYS;
            }

            return $startDate->copy()->addDays($days);
        }

        return match($term) {
            'daily' => $startDate->copy()->addDay(),
            'weekly' => $startDate->copy()->addWeek(),
            'monthly' => $startDate->copy()->addMonth(),
            'yearly' => $startDate->copy()->addYear(),
            'lifetime' => Carbon::maxValue(),
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * @deprecated Use MembershipService::expireActiveMemberships()
     */
    protected function handlePreviousMemberships(int $userId, int $newInvoiceId): void
    {
        app(MembershipService::class)->expireActiveMemberships($userId, $newInvoiceId);
    }

    /**
     * Queue approval email notification
     *
     * @param Invoice $invoice
     * @param User $user
     * @param Package $package
     * @param array $dates
     * @param int $userInvoiceCount
     * @return void
     */
    protected function queueApprovalEmail(Invoice $invoice, User $user, Plan $package, array $dates, int $userInvoiceCount): void
    {
        // Email will be queued/sent via existing email system
        // This is a placeholder for email integration
        // In production, dispatch a job or fire an event here
        
        $mailType = $userInvoiceCount > 1 
            ? 'paymentAcceptedForMembershipExtension' 
            : 'paymentAcceptedForRegistration';

        // TODO: Integrate with existing MegaMailer or Queue email job
        Log::info('Invoice approved - Email queued', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'mail_type' => $mailType,
        ]);
    }

    /**
     * Queue rejection email notification
     *
     * @param Invoice $invoice
     * @param User $user
     * @param Package $package
     * @param string|null $reason
     * @param int $userInvoiceCount
     * @return void
     */
    protected function queueRejectionEmail(Invoice $invoice, User $user, Plan $package, ?string $reason, int $userInvoiceCount): void
    {
        // Email will be queued/sent via existing email system
        // This is a placeholder for email integration
        
        $mailType = $userInvoiceCount > 1 
            ? 'paymentRejectedForMembershipExtension' 
            : 'paymentRejectedForRegistration';

        // TODO: Integrate with existing MegaMailer or Queue email job
        Log::info('Invoice rejected - Email queued', [
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'reason' => $reason,
            'mail_type' => $mailType,
        ]);
    }
}

