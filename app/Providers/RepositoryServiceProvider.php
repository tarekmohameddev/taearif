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

        // Calling module — bind AMI client interface to the real client in production.
        // In tests, swap to FakeAmiClient via $this->app->instance(AmiClientInterface::class, new FakeAmiClient()).
        $this->app->bind(
            \App\Domain\Calling\Contracts\AmiClientInterface::class,
            function ($app) {
                return new \App\Domain\Calling\Services\AmiClient(
                    host:     config('calling.ami.host'),
                    port:     config('calling.ami.port'),
                    username: config('calling.ami.username'),
                    secret:   config('calling.ami.secret'),
                    timeout:  config('calling.ami.timeout', 10),
                );
            }
        );

        $this->app->singleton(
            \App\Domain\Calling\Repositories\AsteriskRealtimeRepository::class
        );

        $this->app->singleton(
            \App\Domain\Calling\Services\SipProvisioningService::class
        );

        $this->app->singleton(
            \App\Domain\Calling\Services\PhoneNumberService::class
        );

        $this->app->singleton(
            \App\Domain\Calling\Services\CallOriginationService::class
        );

        // ── WhatsApp AI Bot ─────────────────────────────────────────────────
        $this->app->singleton(\App\Domain\Ai\Services\LlmDriverFactory::class);
        $this->app->singleton(\App\Domain\Ai\Services\UsageRecorder::class);
        $this->app->singleton(\App\Domain\Ai\Services\CredentialResolver::class);

        $this->app->singleton(\App\Domain\Ai\Knowledge\EmbeddingService::class, function () {
            return new \App\Domain\Ai\Knowledge\EmbeddingService(
                openAiApiKey: (string) env('OPENAI_API_KEY', '')
            );
        });

        $this->app->singleton(\App\Domain\Ai\Knowledge\RetrievalService::class);

        $this->app->singleton(\App\Domain\Ai\Services\LocationResolver::class);

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool::class, function ($app) {
            return new \App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool(
                searchService: $app->make(\App\Services\Matching\PropertySearchService::class),
                locationResolver: $app->make(\App\Domain\Ai\Services\LocationResolver::class),
            );
        });

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\ListingLinkResolver::class);

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\ContextBuilder::class, function ($app) {
            return new \App\Domain\Communication\WhatsApp\Bot\ContextBuilder(
                driverFactory:  $app->make(\App\Domain\Ai\Services\LlmDriverFactory::class),
                retrieval:      $app->make(\App\Domain\Ai\Knowledge\RetrievalService::class),
                propertyTool:   $app->make(\App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool::class),
                usageRecorder:  $app->make(\App\Domain\Ai\Services\UsageRecorder::class),
                linkResolver:   $app->make(\App\Domain\Communication\WhatsApp\Bot\ListingLinkResolver::class),
            );
        });

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\CrmFlywheelService::class);

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\DeliveryService::class, function ($app) {
            return new \App\Domain\Communication\WhatsApp\Bot\DeliveryService(
                commService: $app->make(\App\Domain\Communication\Services\CommunicationServiceImpl::class),
            );
        });

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\RelevanceGate::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\SlotFillingPolicy::class);

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\BotOrchestrator::class, function ($app) {
            return new \App\Domain\Communication\WhatsApp\Bot\BotOrchestrator(
                driverFactory:     $app->make(\App\Domain\Ai\Services\LlmDriverFactory::class),
                usageRecorder:     $app->make(\App\Domain\Ai\Services\UsageRecorder::class),
                contextBuilder:    $app->make(\App\Domain\Communication\WhatsApp\Bot\ContextBuilder::class),
                personaBuilder:    new \App\Domain\Communication\WhatsApp\Bot\PersonaBuilder(),
                groundingVerifier: new \App\Domain\Communication\WhatsApp\Bot\GroundingVerifier(),
                complianceService: new \App\Domain\Communication\WhatsApp\Bot\ComplianceService(),
                handoffService:    new \App\Domain\Communication\WhatsApp\Bot\HandoffService(),
                deliveryService:   $app->make(\App\Domain\Communication\WhatsApp\Bot\DeliveryService::class),
                relevanceGate:     $app->make(\App\Domain\Communication\WhatsApp\Bot\RelevanceGate::class),
                slotFillingPolicy: $app->make(\App\Domain\Communication\WhatsApp\Bot\SlotFillingPolicy::class),
                retrievalService:  $app->make(\App\Domain\Ai\Knowledge\RetrievalService::class),
            );
        });

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Bot\SandboxService::class, function ($app) {
            return new \App\Domain\Communication\WhatsApp\Bot\SandboxService(
                orchestrator: $app->make(\App\Domain\Communication\WhatsApp\Bot\BotOrchestrator::class),
            );
        });
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

