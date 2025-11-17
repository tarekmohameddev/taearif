<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Affiliate\Services\AffiliateService;
use App\Http\Requests\Admin\Affiliate\StoreAffiliateRequest;
use App\Http\Requests\Admin\Affiliate\UpdateAffiliateRequest;
use App\Http\Requests\Admin\Affiliate\UpdateAffiliateStatusRequest;
use App\Http\Requests\Admin\Affiliate\TransactionActionRequest;
use App\Http\Resources\Admin\AffiliateResource;
use App\Http\Resources\Admin\AffiliateDetailResource;
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
 * Affiliate Controller
 *
 * Handles affiliate program endpoints
 */
class AffiliateController extends BaseController
{
    /**
     * @var AffiliateService
     */
    protected $affiliateService;

    /**
     * AffiliateController constructor.
     *
     * @param AffiliateService $affiliateService
     */
    public function __construct(AffiliateService $affiliateService)
    {
        $this->affiliateService = $affiliateService;
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
            $affiliates = $this->affiliateService->getAffiliates($filters, $perPage);

            $collection = new AffiliateCollection($affiliates);
            $resolved = $collection->toArray($request);

            $payload = [
                'affiliates_cards' => $this->formatAffiliateCards($this->affiliateService->getStatistics()),
                'affiliates_users' => $resolved['data'] ?? [],
                'pagination' => $resolved['pagination'] ?? [],
            ];

            return $this->successResponse(
                $payload,
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
            $affiliate = $this->affiliateService->createAffiliate($data);

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
            $affiliate = $this->affiliateService->getAffiliateById($id);
            $affiliate->load(['transactions.referredUser']);

            return $this->successResponse(
                new AffiliateDetailResource($affiliate),
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
            $affiliate = $this->affiliateService->updateAffiliate($id, $data);

            return $this->successResponse(
                new AffiliateResource($affiliate),
                'Affiliate updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update affiliate.');
        }
    }

    /**
     * Update affiliate request status
     *
     * @param UpdateAffiliateStatusRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(UpdateAffiliateStatusRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $affiliate = $this->affiliateService->updateAffiliateStatus($id, $data['request_status']);

            return $this->successResponse(
                new AffiliateResource($affiliate),
                'Affiliate request_status updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update affiliate request_status.');
        }
    }

    /**
     * Get affiliate statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->affiliateService->getStatistics();

            return $this->successResponse(
                $stats,
                'Affiliate statistics retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve affiliate statistics.');
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
            $transactions = $this->affiliateService->getTransactions($filters, $perPage);

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
            $transaction = $this->affiliateService->getTransactionById($id);

            return $this->successResponse(
                new AffiliateTransactionResource($transaction),
                'Transaction retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve transaction.');
        }
    }

    /**
     * Collect transaction/payout
     *
     * @param TransactionActionRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function collectTransaction(TransactionActionRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $transaction = $this->affiliateService->collectTransaction($id, $data['note'] ?? null);

            return $this->successResponse(
                new AffiliateTransactionResource($transaction),
                'Transaction collected successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to collect transaction.');
        }
    }

    /**
     * Centralized error handling for affiliate endpoints.
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
            $errorCode = method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'AFFILIATE_BUSINESS_RULE';

            return $this->errorResponse(
                $e->getMessage(),
                $errorCode,
                $status
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'AFFILIATE_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }

    /**
     * Format the affiliate statistics for dashboard cards.
     */
    protected function formatAffiliateCards(array $stats): array
    {
        $affiliates = $stats['affiliates'] ?? [];
        $transactions = $stats['transactions'] ?? [];

        return [
            'total_partners' => (int) ($affiliates['total'] ?? 0),
            'total_referrals' => (int) ($transactions['total'] ?? 0),
            'total_conversions' => (int) ($transactions['approved'] ?? 0),
            'total_profits' => (float) ($transactions['total_amount'] ?? 0.0),
        ];
    }
}


