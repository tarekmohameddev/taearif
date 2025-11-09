<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Referral\Services\ReferralService;
use App\Http\Requests\Admin\Referral\StoreAffiliateRequest;
use App\Http\Requests\Admin\Referral\UpdateAffiliateRequest;
use App\Http\Requests\Admin\Referral\TransactionActionRequest;
use App\Http\Resources\Admin\AffiliateResource;
use App\Http\Resources\Admin\AffiliateCollection;
use App\Http\Resources\Admin\AffiliateTransactionResource;
use App\Http\Resources\Admin\AffiliateTransactionCollection;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Referral Controller
 * 
 * Handles referral/affiliate program endpoints
 */
class ReferralController extends BaseController
{
    /**
     * @var ReferralService
     */
    protected $referralService;

    /**
     * ReferralController constructor.
     *
     * @param ReferralService $referralService
     */
    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Get paginated list of affiliates
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'request_status',
                'start_date',
                'end_date',
                'order_by',
                'order_dir',
            ]);

            $perPage = min((int) $request->input('per_page', 20), 100);
            $affiliates = $this->referralService->getAffiliates($filters, $perPage);

            return $this->successResponse(
                new AffiliateCollection($affiliates),
                'Affiliates retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve affiliates.');
        }
    }

    /**
     * Create a new affiliate
     * 
     * @param StoreAffiliateRequest $request
     * @return JsonResponse
     */
    public function store(StoreAffiliateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $affiliate = $this->referralService->createAffiliate($data);

            return $this->successResponse(
                new AffiliateResource($affiliate),
                'Affiliate created successfully',
                Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create affiliate.');
        }
    }

    /**
     * Get affiliate by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $affiliate = $this->referralService->getAffiliateById($id);

            return $this->successResponse(
                new AffiliateResource($affiliate),
                'Affiliate retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve affiliate.');
        }
    }

    /**
     * Update existing affiliate
     * 
     * @param UpdateAffiliateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateAffiliateRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $affiliate = $this->referralService->updateAffiliate($id, $data);

            return $this->successResponse(
                new AffiliateResource($affiliate),
                'Affiliate updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update affiliate.');
        }
    }

    /**
     * Get referral statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->referralService->getStatistics();

            return $this->successResponse(
                $stats,
                'Referral statistics retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve referral statistics.');
        }
    }

    /**
     * Get paginated list of transactions
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type',
                'affiliate_id',
                'min_amount',
                'max_amount',
                'start_date',
                'end_date',
                'order_by',
                'order_dir',
            ]);

            $perPage = min((int) $request->input('per_page', 20), 100);
            $transactions = $this->referralService->getTransactions($filters, $perPage);

            return $this->successResponse(
                new AffiliateTransactionCollection($transactions),
                'Transactions retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve transactions.');
        }
    }

    /**
     * Get transaction by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function showTransaction(int $id): JsonResponse
    {
        try {
            $transaction = $this->referralService->getTransactionById($id);

            return $this->successResponse(
                new AffiliateTransactionResource($transaction),
                'Transaction retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve transaction.');
        }
    }

    /**
     * Approve transaction/payout
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function approveTransaction(int $id): JsonResponse
    {
        try {
            $transaction = $this->referralService->approveTransaction($id);

            return $this->successResponse(
                new AffiliateTransactionResource($transaction),
                'Transaction approved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to approve transaction.');
        }
    }

    /**
     * Reject transaction/payout
     * 
     * @param TransactionActionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function rejectTransaction(TransactionActionRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $transaction = $this->referralService->rejectTransaction($id, $data['note'] ?? null);

            return $this->successResponse(
                new AffiliateTransactionResource($transaction),
                'Transaction rejected successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to reject transaction.');
        }
    }

    /**
     * Mark transaction as paid
     * 
     * @param TransactionActionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsPaid(TransactionActionRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $transaction = $this->referralService->markTransactionAsPaid($id, $data['note'] ?? null);

            return $this->successResponse(
                new AffiliateTransactionResource($transaction),
                'Transaction marked as paid successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to mark transaction as paid.');
        }
    }

    /**
     * Centralized error handling for referral endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            $status = $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY;
            $errorCode = method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'REFERRAL_BUSINESS_RULE';

            return $this->errorResponse(
                $e->getMessage(),
                $errorCode,
                $status
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'REFERRAL_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

