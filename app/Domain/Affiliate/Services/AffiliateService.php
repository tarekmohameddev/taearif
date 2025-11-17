<?php

namespace App\Domain\Affiliate\Services;

use App\Domain\Affiliate\Models\Affiliate;
use App\Domain\Affiliate\Models\AffiliateTransaction;
use App\Domain\Affiliate\Repositories\AffiliateRepositoryInterface;
use App\Domain\Affiliate\Repositories\AffiliateTransactionRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Affiliate Service
 *
 * Business logic for managing affiliates and the affiliate program
 */
class AffiliateService extends BaseService
{
    /**
     * @var AffiliateRepositoryInterface
     */
    protected $affiliateRepository;

    /**
     * @var AffiliateTransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * AffiliateService constructor.
     *
     * @param AffiliateRepositoryInterface $affiliateRepository
     * @param AffiliateTransactionRepositoryInterface $transactionRepository
     */
    public function __construct(
        AffiliateRepositoryInterface $affiliateRepository,
        AffiliateTransactionRepositoryInterface $transactionRepository
    ) {
        $this->affiliateRepository = $affiliateRepository;
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * Get paginated affiliates with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAffiliates(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->affiliateRepository->searchAndPaginate($filters, $perPage);
    }

    /**
     * Get affiliate by ID
     *
     * @param int $id
     * @return Affiliate
     * @throws ResourceNotFoundException
     */
    public function getAffiliateById(int $id): Affiliate
    {
        $affiliate = $this->affiliateRepository->findById($id);

        if (!$affiliate) {
            throw new ResourceNotFoundException('Affiliate not found');
        }

        $affiliate->load(['user', 'transactions']);

        return $affiliate;
    }

    /**
     * Create a new affiliate
     *
     * @param array $data
     * @return Affiliate
     */
    public function createAffiliate(array $data): Affiliate
    {
        return $this->executeInTransaction(function () use ($data) {
            $affiliate = $this->affiliateRepository->create($data);

            return $affiliate->load(['user']);
        });
    }

    /**
     * Update existing affiliate
     *
     * @param int $id
     * @param array $data
     * @return Affiliate
     * @throws ResourceNotFoundException
     */
    public function updateAffiliate(int $id, array $data): Affiliate
    {
        $affiliate = $this->affiliateRepository->findById($id);

        if (!$affiliate) {
            throw new ResourceNotFoundException('Affiliate not found');
        }

        return $this->executeInTransaction(function () use ($affiliate, $data) {
            $updated = $this->affiliateRepository->update($affiliate, $data);

            return $updated->load(['user']);
        });
    }

    /**
     * Update affiliate request status
     *
     * @param int $id
     * @param string $status
     * @return Affiliate
     * @throws ResourceNotFoundException
     */
    public function updateAffiliateStatus(int $id, string $status): Affiliate
    {
        $affiliate = $this->affiliateRepository->findById($id);

        if (!$affiliate) {
            throw new ResourceNotFoundException('Affiliate not found');
        }

        return $this->executeInTransaction(function () use ($affiliate, $status) {
            return $this->affiliateRepository->updateRequestStatus($affiliate, $status);
        });
    }

    /**
     * Get paginated transactions with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTransactions(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->transactionRepository->searchAndPaginate($filters, $perPage);
    }

    /**
     * Get transaction by ID
     *
     * @param int $id
     * @return AffiliateTransaction
     * @throws ResourceNotFoundException
     */
    public function getTransactionById(int $id): AffiliateTransaction
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            throw new ResourceNotFoundException('Transaction not found');
        }

        $transaction->load(['affiliate.user', 'referredUser']);

        return $transaction;
    }

    /**
     * Collect transaction (finalize payout)
     *
     * @param int $id
     * @param string|null $note
     * @return AffiliateTransaction
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function collectTransaction(int $id, ?string $note = null): AffiliateTransaction
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            throw new ResourceNotFoundException('Transaction not found');
        }

        if ($transaction->type !== 'pending') {
            throw new BusinessLogicException(
                'Only pending transactions can be collected',
                'AFFILIATE_TRANSACTION_NOT_PENDING',
                422
            );
        }

        return $this->executeInTransaction(function () use ($transaction, $note) {
            return $this->transactionRepository->collectTransaction($transaction, $note);
        });
    }

    /**
     * Get affiliate statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'affiliates' => [
                'total' => Affiliate::count(),
                'pending' => Affiliate::byStatus('pending')->count(),
                'approved' => Affiliate::byStatus('approved')->count(),
                'rejected' => Affiliate::byStatus('rejected')->count(),
            ],
            'transactions' => [
                'total' => AffiliateTransaction::count(),
                'pending' => AffiliateTransaction::pending()->count(),
                'collected' => AffiliateTransaction::collected()->count(),
                'total_amount' => (float) AffiliateTransaction::sum('amount'),
                'pending_amount' => (float) AffiliateTransaction::pending()->sum('amount'),
                'collected_amount' => (float) AffiliateTransaction::collected()->sum('amount'),
            ],
        ];
    }
}


