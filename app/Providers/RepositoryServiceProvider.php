<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Repository Service Provider
 *
 * Binds repository interfaces to their concrete implementations
 * Following Dependency Inversion Principle (SOLID)
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository bindings
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Domain\Admin\Repositories\AdminRepositoryInterface::class,
            function () {
                return new \App\Domain\Admin\Repositories\AdminRepository(
                    new \App\Domain\Admin\Models\Admin()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Admin\Repositories\ImpersonationRepositoryInterface::class,
            function () {
                return new \App\Domain\Admin\Repositories\ImpersonationRepository(
                    new \App\Domain\Admin\Models\AdminImpersonation()
                );
            }
        );

        $this->app->bind(
            \App\Domain\User\Repositories\UserRepositoryInterface::class,
            function () {
                return new \App\Domain\User\Repositories\UserRepository(
                    new \App\Domain\User\Models\User()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Billing\Repositories\SubscriptionRepositoryInterface::class,
            function () {
                return new \App\Domain\Billing\Repositories\SubscriptionRepository(
                    new \App\Domain\Billing\Models\Subscription()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Billing\Repositories\InvoiceRepositoryInterface::class,
            function () {
                return new \App\Domain\Billing\Repositories\InvoiceRepository(
                    new \App\Domain\Billing\Models\Invoice()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Crm\Repositories\LeadRepositoryInterface::class,
            function () {
                return new \App\Domain\Crm\Repositories\LeadRepository(
                    new \App\Domain\Crm\Models\Lead()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Crm\Repositories\LeadActivityRepositoryInterface::class,
            function () {
                return new \App\Domain\Crm\Repositories\LeadActivityRepository(
                    new \App\Domain\Crm\Models\LeadActivity()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Domain\Repositories\CustomDomainRepositoryInterface::class,
            function () {
                return new \App\Domain\Domain\Repositories\CustomDomainRepository(
                    new \App\Domain\Domain\Models\CustomDomain()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Marketing\Repositories\WhatsAppTemplateRepositoryInterface::class,
            function () {
                return new \App\Domain\Marketing\Repositories\WhatsAppTemplateRepository(
                    new \App\Domain\Marketing\Models\WhatsAppTemplate()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Support\Repositories\InquiryRepositoryInterface::class,
            function () {
                return new \App\Domain\Support\Repositories\InquiryRepository(
                    new \App\Domain\Support\Models\Inquiry()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Affiliate\Repositories\AffiliateRepositoryInterface::class,
            function () {
                return new \App\Domain\Affiliate\Repositories\AffiliateRepository(
                    new \App\Domain\Affiliate\Models\Affiliate()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Affiliate\Repositories\AffiliateTransactionRepositoryInterface::class,
            function () {
                return new \App\Domain\Affiliate\Repositories\AffiliateTransactionRepository(
                    new \App\Domain\Affiliate\Models\AffiliateTransaction()
                );
            }
        );

        $this->app->bind(
            \App\Domain\Billing\Repositories\PlanRepositoryInterface::class,
            function () {
                return new \App\Domain\Billing\Repositories\PlanRepository(
                    new \App\Domain\Billing\Models\Plan()
                );
            }
        );
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}

