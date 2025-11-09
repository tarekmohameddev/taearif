<?php

namespace App\Domain\Marketing\Services;

use App\Domain\Marketing\Models\WhatsAppTemplate;
use App\Domain\Marketing\Repositories\WhatsAppTemplateRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Models\BasicSetting;
use App\Models\Language;

/**
 * Marketing Service
 *
 * Business logic for marketing and automation features
 */
class MarketingService extends BaseService
{
    /**
     * @var WhatsAppTemplateRepositoryInterface
     */
    protected $templateRepository;

    /**
     * MarketingService constructor.
     *
     * @param WhatsAppTemplateRepositoryInterface $templateRepository
     */
    public function __construct(WhatsAppTemplateRepositoryInterface $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    /**
     * Get marketing overview/dashboard
     *
     * @return array
     */
    public function getMarketingOverview(): array
    {
        return [
            'whatsapp' => [
                'total_templates' => WhatsAppTemplate::count(),
                'active_templates' => WhatsAppTemplate::active()->count(),
                'inactive_templates' => WhatsAppTemplate::inactive()->count(),
            ],
            'recent_templates' => WhatsAppTemplate::orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * Get paginated templates with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getTemplates(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->templateRepository->searchAndPaginate($filters, $perPage);
    }

    /**
     * Get template by ID
     *
     * @param int $id
     * @return WhatsAppTemplate
     * @throws ResourceNotFoundException
     */
    public function getTemplateById(int $id): WhatsAppTemplate
    {
        $template = $this->templateRepository->findById($id);

        if (!$template) {
            throw new ResourceNotFoundException('Template not found');
        }

        return $template;
    }

    /**
     * Create a new template
     *
     * @param array $data
     * @return WhatsAppTemplate
     * @throws BusinessLogicException
     */
    public function createTemplate(array $data): WhatsAppTemplate
    {
        // Check for duplicate name
        if ($this->templateRepository->exists('name', $data['name'])) {
            throw new BusinessLogicException(
                'Template with this name already exists',
                'WHATSAPP_TEMPLATE_DUPLICATE',
                422
            );
        }

        return $this->executeInTransaction(function () use ($data) {
            return $this->templateRepository->create($data);
        });
    }

    /**
     * Update existing template
     *
     * @param int $id
     * @param array $data
     * @return WhatsAppTemplate
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateTemplate(int $id, array $data): WhatsAppTemplate
    {
        $template = $this->templateRepository->findById($id);

        if (!$template) {
            throw new ResourceNotFoundException('Template not found');
        }

        // Check for duplicate name (excluding current template)
        if (isset($data['name']) && $data['name'] !== $template->name) {
            if ($this->templateRepository->exists('name', $data['name'])) {
                throw new BusinessLogicException(
                    'Template with this name already exists',
                    'WHATSAPP_TEMPLATE_DUPLICATE',
                    422
                );
            }
        }

        return $this->executeInTransaction(function () use ($template, $data) {
            return $this->templateRepository->update($template, $data);
        });
    }

    /**
     * Delete a template
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     */
    public function deleteTemplate(int $id): bool
    {
        $template = $this->templateRepository->findById($id);

        if (!$template) {
            throw new ResourceNotFoundException('Template not found');
        }

        return $this->executeInTransaction(function () use ($template) {
            return $this->templateRepository->delete($template);
        });
    }

    /**
     * Toggle template status
     *
     * @param int $id
     * @return WhatsAppTemplate
     * @throws ResourceNotFoundException
     */
    public function toggleTemplateStatus(int $id): WhatsAppTemplate
    {
        $template = $this->templateRepository->findById($id);

        if (!$template) {
            throw new ResourceNotFoundException('Template not found');
        }

        return $this->executeInTransaction(function () use ($template) {
            return $this->templateRepository->toggleStatus($template);
        });
    }

    /**
     * Get active templates
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveTemplates()
    {
        return $this->templateRepository->getActive();
    }

    /**
     * Get templates by type
     *
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTemplatesByType(string $type)
    {
        return $this->templateRepository->getByType($type);
    }

    /**
     * Get marketing statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'whatsapp_templates' => [
                'total' => WhatsAppTemplate::count(),
                'active' => WhatsAppTemplate::active()->count(),
                'inactive' => WhatsAppTemplate::inactive()->count(),
                'by_language' => [
                    'ar' => WhatsAppTemplate::byLanguage('ar')->count(),
                    'en' => WhatsAppTemplate::byLanguage('en')->count(),
                ],
            ],
        ];
    }

    /**
     * Get all automated messages
     *
     * @return array
     */
    public function getAutomatedMessages(): array
    {
        $currentLang = Language::where('is_default', 1)->first();
        $settings = BasicSetting::where('language_id', $currentLang->id)->first();

        if (!$settings) {
            return [
                'welcome' => $this->getDefaultAutomatedMessage('welcome'),
                'subscription_expiring' => $this->getDefaultAutomatedMessage('subscription_expiring'),
                'subscription_expired' => $this->getDefaultAutomatedMessage('subscription_expired'),
                'password_reset' => $this->getDefaultAutomatedMessage('password_reset'),
            ];
        }

        if (!$currentLang) {
            return $this->getDefaultAutomatedMessages();
        }

        $settings = BasicSetting::where('language_id', $currentLang->id)->first();

        if (!$settings) {
            return $this->getDefaultAutomatedMessages();
        }

        return [
            'welcome' => $this->formatWelcomeMessage($settings),
            'subscription_expiring' => $this->formatSubscriptionExpiringMessage($settings),
            'subscription_expired' => $this->formatSubscriptionExpiredMessage($settings),
            'password_reset' => $this->formatPasswordResetMessage($settings),
        ];
    }

    /**
     * Get automated message by type
     *
     * @param string $type
     * @return array|null
     */
    public function getAutomatedMessageByType(string $type): ?array
    {
        $currentLang = Language::where('is_default', 1)->first();

        if (!$currentLang) {
            return $this->getDefaultAutomatedMessage($type);
        }

        $settings = BasicSetting::where('language_id', $currentLang->id)->first();

        if (!$settings) {
            return $this->getDefaultAutomatedMessage($type);
        }

        return match($type) {
            'welcome' => $this->formatWelcomeMessage($settings),
            'subscription_expiring' => $this->formatSubscriptionExpiringMessage($settings),
            'subscription_expired' => $this->formatSubscriptionExpiredMessage($settings),
            'password_reset' => $this->formatPasswordResetMessage($settings),
            default => null,
        };
    }

    /**
     * Update automated message
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    public function updateAutomatedMessage(string $type, array $data): array
    {
        return $this->executeInTransaction(function () use ($type, $data) {
            $currentLang = Language::where('is_default', 1)->first();
            if (!$currentLang) {
                throw new ResourceNotFoundException('Default language not configured');
            }

            $settings = BasicSetting::where('language_id', $currentLang->id)->first();

            if (!$settings) {
                throw new ResourceNotFoundException('Settings not found');
            }

            $updateData = $this->prepareUpdateData($type, $data);
            $settings->update($updateData);

            return $this->getAutomatedMessageByType($type);
        });
    }

    /**
     * Get WhatsApp settings
     *
     * @return array
     */
    public function getWhatsAppSettings(): array
    {
        $currentLang = Language::where('is_default', 1)->first();

        if (!$currentLang) {
            return $this->getDefaultWhatsAppSettings();
        }

        $settings = BasicSetting::where('language_id', $currentLang->id)->first();

        if (!$settings) {
            return $this->getDefaultWhatsAppSettings();
        }

        return [
            'service' => $settings->whatsapp_service ?? 'meta',
            'notifications_enabled' => (bool) $settings->whatsapp_notifications_enabled,
            'meta' => [
                'access_token' => $settings->meta_access_token ? '***' . substr($settings->meta_access_token, -4) : null,
                'phone_number_id' => $settings->meta_phone_number_id,
                'business_account_id' => $settings->meta_business_account_id,
            ],
            'evolution' => [
                'api_url' => $settings->evolution_api_url,
                'instance_name' => $settings->evolution_instance_name,
                'phone_number' => $settings->evolution_phone_number,
            ],
        ];
    }

    /**
     * Update WhatsApp settings
     *
     * @param array $data
     * @return array
     */
    public function updateWhatsAppSettings(array $data): array
    {
        return $this->executeInTransaction(function () use ($data) {
            $currentLang = Language::where('is_default', 1)->first();
            if (!$currentLang) {
                throw new ResourceNotFoundException('Default language not configured');
            }

            $settings = BasicSetting::where('language_id', $currentLang->id)->first();

            if (!$settings) {
                throw new ResourceNotFoundException('Settings not found');
            }

            $updateData = [];
            
            if (isset($data['service'])) {
                $updateData['whatsapp_service'] = $data['service'];
            }
            
            if (isset($data['notifications_enabled'])) {
                $updateData['whatsapp_notifications_enabled'] = $data['notifications_enabled'];
            }

            if (isset($data['meta'])) {
                if (isset($data['meta']['access_token'])) {
                    $updateData['meta_access_token'] = $data['meta']['access_token'];
                }
                if (isset($data['meta']['phone_number_id'])) {
                    $updateData['meta_phone_number_id'] = $data['meta']['phone_number_id'];
                }
                if (isset($data['meta']['business_account_id'])) {
                    $updateData['meta_business_account_id'] = $data['meta']['business_account_id'];
                }
            }

            if (isset($data['evolution'])) {
                if (isset($data['evolution']['api_url'])) {
                    $updateData['evolution_api_url'] = $data['evolution']['api_url'];
                }
                if (isset($data['evolution']['api_key'])) {
                    $updateData['evolution_api_key'] = $data['evolution']['api_key'];
                }
                if (isset($data['evolution']['instance_name'])) {
                    $updateData['evolution_instance_name'] = $data['evolution']['instance_name'];
                }
                if (isset($data['evolution']['phone_number'])) {
                    $updateData['evolution_phone_number'] = $data['evolution']['phone_number'];
                }
            }

            $settings->update($updateData);

            return $this->getWhatsAppSettings();
        });
    }

    /**
     * Format welcome message
     */
    protected function formatWelcomeMessage($settings): array
    {
        return [
            'type' => 'welcome',
            'enabled' => (bool) $settings->welcome_message_enabled,
            'text' => $settings->welcome_message_text,
            'delay' => $settings->welcome_message_delay ?? 5,
            'template' => $settings->welcome_message_template,
            'api' => $settings->welcome_message_api ?? 'meta',
        ];
    }

    /**
     * Format subscription expiring message
     */
    protected function formatSubscriptionExpiringMessage($settings): array
    {
        return [
            'type' => 'subscription_expiring',
            'enabled' => (bool) $settings->subscription_expiration_enabled,
            'text' => $settings->subscription_expiration_text,
            'days_before' => $settings->subscription_expiration_days_before ?? 3,
            'template' => $settings->subscription_expiration_template,
            'send_time' => $settings->subscription_expiration_send_time ?? '09:00',
            'api' => $settings->subscription_expiration_api ?? 'meta',
        ];
    }

    /**
     * Format subscription expired message
     */
    protected function formatSubscriptionExpiredMessage($settings): array
    {
        return [
            'type' => 'subscription_expired',
            'enabled' => (bool) $settings->subscription_expired_enabled,
            'text' => $settings->subscription_expired_text,
            'template' => $settings->subscription_expired_template,
            'send_time' => $settings->subscription_expired_send_time ?? '09:00',
            'api' => $settings->subscription_expired_api ?? 'meta',
        ];
    }

    /**
     * Format password reset message
     */
    protected function formatPasswordResetMessage($settings): array
    {
        return [
            'type' => 'password_reset',
            'enabled' => (bool) $settings->password_reset_enabled,
            'text' => $settings->password_reset_text,
            'template' => $settings->password_reset_template,
            'api' => $settings->password_reset_api ?? 'meta',
        ];
    }

    /**
     * Prepare update data based on type
     */
    protected function prepareUpdateData(string $type, array $data): array
    {
        $updateData = [];

        switch ($type) {
            case 'welcome':
                if (isset($data['enabled'])) $updateData['welcome_message_enabled'] = $data['enabled'];
                if (isset($data['text'])) $updateData['welcome_message_text'] = $data['text'];
                if (isset($data['delay'])) $updateData['welcome_message_delay'] = $data['delay'];
                if (isset($data['template'])) $updateData['welcome_message_template'] = $data['template'];
                if (isset($data['api'])) $updateData['welcome_message_api'] = $data['api'];
                break;

            case 'subscription_expiring':
                if (isset($data['enabled'])) $updateData['subscription_expiration_enabled'] = $data['enabled'];
                if (isset($data['text'])) $updateData['subscription_expiration_text'] = $data['text'];
                if (isset($data['days_before'])) $updateData['subscription_expiration_days_before'] = $data['days_before'];
                if (isset($data['template'])) $updateData['subscription_expiration_template'] = $data['template'];
                if (isset($data['send_time'])) $updateData['subscription_expiration_send_time'] = $data['send_time'];
                if (isset($data['api'])) $updateData['subscription_expiration_api'] = $data['api'];
                break;

            case 'subscription_expired':
                if (isset($data['enabled'])) $updateData['subscription_expired_enabled'] = $data['enabled'];
                if (isset($data['text'])) $updateData['subscription_expired_text'] = $data['text'];
                if (isset($data['template'])) $updateData['subscription_expired_template'] = $data['template'];
                if (isset($data['send_time'])) $updateData['subscription_expired_send_time'] = $data['send_time'];
                if (isset($data['api'])) $updateData['subscription_expired_api'] = $data['api'];
                break;

            case 'password_reset':
                if (isset($data['enabled'])) $updateData['password_reset_enabled'] = $data['enabled'];
                if (isset($data['text'])) $updateData['password_reset_text'] = $data['text'];
                if (isset($data['template'])) $updateData['password_reset_template'] = $data['template'];
                if (isset($data['api'])) $updateData['password_reset_api'] = $data['api'];
                break;
        }

        return $updateData;
    }

    /**
     * Get default automated messages map.
     */
    protected function getDefaultAutomatedMessages(): array
    {
        return [
            'welcome' => $this->getDefaultAutomatedMessage('welcome'),
            'subscription_expiring' => $this->getDefaultAutomatedMessage('subscription_expiring'),
            'subscription_expired' => $this->getDefaultAutomatedMessage('subscription_expired'),
            'password_reset' => $this->getDefaultAutomatedMessage('password_reset'),
        ];
    }

    /**
     * Get default automated message
     */
    protected function getDefaultAutomatedMessage(string $type): array
    {
        $defaults = [
            'welcome' => [
                'type' => 'welcome',
                'enabled' => false,
                'text' => 'مرحباً بك في منصة تعاريف!',
                'delay' => 5,
                'template' => null,
                'api' => 'meta',
            ],
            'subscription_expiring' => [
                'type' => 'subscription_expiring',
                'enabled' => false,
                'text' => 'تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً.',
                'days_before' => 3,
                'template' => null,
                'send_time' => '09:00',
                'api' => 'meta',
            ],
            'subscription_expired' => [
                'type' => 'subscription_expired',
                'enabled' => false,
                'text' => 'انتهى اشتراكك وتم نقلك إلى الباقة المجانية.',
                'template' => null,
                'send_time' => '09:00',
                'api' => 'meta',
            ],
            'password_reset' => [
                'type' => 'password_reset',
                'enabled' => false,
                'text' => 'رمز إعادة تعيين كلمة المرور: {code}',
                'template' => null,
                'api' => 'meta',
            ],
        ];

        return $defaults[$type] ?? [];
    }

    /**
     * Get default WhatsApp settings
     */
    protected function getDefaultWhatsAppSettings(): array
    {
        return [
            'service' => 'meta',
            'notifications_enabled' => false,
            'meta' => [
                'access_token' => null,
                'phone_number_id' => null,
                'business_account_id' => null,
            ],
            'evolution' => [
                'api_url' => null,
                'instance_name' => null,
                'phone_number' => null,
            ],
        ];
    }
}

