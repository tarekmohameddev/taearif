# API Feature Inventory (from routes/api.php only)

Factual inventory of API features for E2E coverage planning. Source: `routes/api.php`. No web routes, Blade, or admin panels. Multi-tenant, single database.

**Legend:** Auth = auth required (yes/no). Tenant-scoped = tenant/owner context (yes/no). Visibility = Public | Internal.

---

## 1. Authentication (tenant / employee)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/register` | No | No | Public |
| POST | `/login` | No | No | Public |
| POST | `/logout` | Yes | No | Internal |
| POST | `/auth/forgot-password` | No | No | Public |
| POST | `/auth/verify-reset-code` | No | No | Public |
| GET | `/auth/google/redirect` | No (web) | No | Public |
| GET | `/auth/google/callback` | No (web) | No | Public |

---

## 2. User profile

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/user` | Yes | Yes | Internal |
| GET | `/user/getUserInfo` | Yes | Yes | Internal |
| POST | `/user-read-message` | Yes | Yes | Internal |

---

## 3. Affiliate

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/affiliate/register` | Yes | Yes | Internal |
| GET | `/affiliate` | Yes | Yes | Internal |

---

## 4. Impersonation

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/impersonate/{user}` | Yes | Yes | Internal |
| POST | `/impersonate/{user}/revoke` | Yes | Yes | Internal |

---

## 5. Payments / checkout / onboarding

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/make-payment` | Yes | Yes | Internal |
| POST | `/make-payment-app` | Yes | Yes | Internal |
| POST | `/onboarding` | Yes | Yes | Internal |

---

## 6. Dashboard (require active package)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/dashboard` | Yes + package | Yes | Internal |
| GET | `/dashboard/summary` | Yes + package | Yes | Internal |
| POST | `/dashboard/visitors` | Yes + package | Yes | Internal |
| GET | `/dashboard/devices` | Yes + package | Yes | Internal |
| GET | `/dashboard/traffic-sources` | Yes + package | Yes | Internal |
| GET | `/dashboard/most-visited-pages` | Yes + package | Yes | Internal |
| GET | `/dashboard/setup-progress` | Yes + package | Yes | Internal |
| GET | `/dashboard/recent-activity` | Yes + package | Yes | Internal |
| GET | `/analytics/search` | Yes + package | Yes | Internal |
| GET | `/analytics/page-locations` | Yes + package | Yes | Internal |
| GET | `/analytics/today` | Yes + package | Yes | Internal |
| GET | `/analytics/realtime` | Yes + package | Yes | Internal |

*(Excluded: debug/diagnostic GA endpoints.)*

---

## 7. Analytics (v1, auth only)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/analytics/dashboard` | Yes | Yes | Internal |
| GET | `/v1/analytics/top-pages` | Yes | Yes | Internal |
| GET | `/v1/analytics/top-posts` | Yes | Yes | Internal |
| GET | `/v1/analytics/views-summary` | Yes | Yes | Internal |
| GET | `/v1/analytics/ga4/dashboard` | Yes | Yes | Internal |
| GET | `/v1/analytics/ga4/top-pages` | Yes | Yes | Internal |
| GET | `/v1/analytics/ga4/properties-visits` | Yes | Yes | Internal |

---

## 8. Blog (legacy)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/blogs` | Yes | Yes | Internal |
| POST | `/blogs/{id}` | Yes | Yes | Internal |
| DELETE | `/blogs/{id}` | Yes | Yes | Internal |
| POST | `/blogs/upload-image` | Yes | Yes | Internal |
| GET | `/blogs` | Yes | Yes | Internal |
| GET | `/blogs/{id}` | Yes | Yes | Internal |
| GET | `/blog-categories` | Yes | Yes | Internal |

---

## 9. Blog posts / categories

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/posts` | Yes | Yes | Internal |
| GET | `/posts/{slug}` | Yes | Yes | Internal |
| GET | `/categories` | Yes | Yes | Internal |
| GET | `/categories/{slug}` | Yes | Yes | Internal |
| GET | `/categories/{slug}/posts` | Yes | Yes | Internal |
| POST | `/posts` | Yes | Yes | Internal |
| PUT | `/posts/{slug}` | Yes | Yes | Internal |
| DELETE | `/posts/{slug}` | Yes | Yes | Internal |
| POST | `/media` | Yes | Yes | Internal |
| POST | `/categories` | Yes | Yes | Internal |
| PUT | `/categories/{slug}` | Yes | Yes | Internal |
| DELETE | `/categories/{slug}` | Yes | Yes | Internal |

---

## 10. Contracts

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/contracts` | Yes | Yes | Internal |
| GET | `/contracts/{id}` | Yes | Yes | Internal |
| POST | `/contracts` | Yes | Yes | Internal |
| PUT | `/contracts/{id}` | Yes | Yes | Internal |
| DELETE | `/contracts/{id}` | Yes | Yes | Internal |
| GET | `/contracts/statistics` | Yes | Yes | Internal |
| GET | `/contracts/customer/{customerId}` | Yes | Yes | Internal |
| GET | `/contracts/rental/{rentalId}` | Yes | Yes | Internal |

---

## 11. Rental contracts (legacy)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/rental-contracts` | Yes | Yes | Internal |
| GET | `/rental-contracts/statistics` | Yes | Yes | Internal |
| GET | `/rental-contracts/daily-follow-up` | Yes | Yes | Internal |
| GET | `/rental-contracts/all-contracts` | Yes | Yes | Internal |
| GET | `/rental-contracts/filter` | Yes | Yes | Internal |
| GET | `/rental-contracts/rental/{rentalId}` | Yes | Yes | Internal |
| POST | `/rental-contracts` | Yes | Yes | Internal |
| GET | `/rental-contracts/{id}` | Yes | Yes | Internal |
| PUT | `/rental-contracts/{id}` | Yes | Yes | Internal |
| DELETE | `/rental-contracts/{id}` | Yes | Yes | Internal |
| POST | `/rental-contracts/{id}/terminate` | Yes | Yes | Internal |
| PATCH | `/rental-contracts/{id}/status` | Yes | Yes | Internal |

---

## 12. Projects

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/projects` | Yes + can:projects.view | Yes | Internal |
| GET | `/projects/{id}` | Yes + can:projects.view | Yes | Internal |
| POST | `/projects` | Yes + can:projects.create | Yes | Internal |
| POST | `/projects/{id}` | Yes + can:projects.update | Yes | Internal |
| DELETE | `/projects/{id}` | Yes + can:projects.delete | Yes | Internal |
| PATCH | `/projects/{id}/toggle-featured` | Yes + can:projects.update | Yes | Internal |
| GET | `/user/projects` | Yes + can:projects.view | Yes | Internal |

---

## 13. Properties

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/properties/reorder-featured` | Yes + can:properties.reorder | Yes | Internal |
| POST | `/properties/reorder` | Yes + can:properties.reorder | Yes | Internal |
| GET | `/properties/categories` | Yes (audit.ctx) | Yes | Internal |
| GET | `/properties` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/filter-options` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/cards` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/available-units` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/export` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/export-for-import` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/drafts` | Yes + can:properties.view | Yes | Internal |
| GET | `/properties/drafts/{id}` | Yes + can:properties.view | Yes | Internal |
| PATCH | `/properties/drafts/{id}` | Yes + can:properties.update | Yes | Internal |
| POST | `/properties/drafts/{id}/complete` | Yes + can:properties.create | Yes | Internal |
| POST | `/properties/drafts/bulk-complete` | Yes + can:properties.create | Yes | Internal |
| GET | `/properties/{id}` | Yes + can:properties.view | Yes | Internal |
| POST | `/properties/bulk-import` | Yes + can:properties.create | Yes | Internal |
| POST | `/properties` | Yes + can:properties.create | Yes | Internal |
| POST | `/properties/upload-deed-image` | Yes + can:properties.create | Yes | Internal |
| POST | `/properties/{id}` | Yes + can:properties.update | Yes | Internal |
| DELETE | `/properties/{id}` | Yes + can:properties.delete | Yes | Internal |
| PATCH | `/properties/{id}/toggle-featured` | Yes + can:properties.update | Yes | Internal |
| POST | `/properties/{id}/toggle-status` | Yes + can:properties.update | Yes | Internal |
| POST | `/properties/{propertyId}/duplicate` | Yes + can:properties.create | Yes | Internal |
| GET | `/property/facades` | Yes + can:properties.view | Yes | Internal |
| GET | `/property-faqs` | Yes (audit.ctx) | Yes | Internal |
| GET | `/buildings` | Yes (audit.ctx) | Yes | Internal |
| GET | `/buildings/{id}` | Yes (audit.ctx) | Yes | Internal |
| POST | `/buildings` | Yes (audit.ctx) | Yes | Internal |
| POST | `/buildings/upload-image` | Yes (audit.ctx) | Yes | Internal |
| POST | `/buildings/upload-deed-image` | Yes (audit.ctx) | Yes | Internal |
| PUT | `/buildings/{id}` | Yes (audit.ctx) | Yes | Internal |
| DELETE | `/buildings/{id}` | Yes (audit.ctx) | Yes | Internal |

---

## 14. Content / upload / regions / settings

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/content/sections` | Yes | Yes | Internal |
| POST | `/upload` | Yes | Yes | Internal |
| POST | `/upload-multiple` | Yes | Yes | Internal |
| POST | `/delete-file` | Yes | Yes | Internal |
| GET | `regions` | Yes | Yes | Internal |
| GET | `regions/{region}` | Yes | Yes | Internal |
| GET | `/content/footer` | Yes | Yes | Internal |
| PUT | `/content/footer` | Yes | Yes | Internal |
| GET | `/content/general` | Yes | Yes | Internal |
| PUT | `/content/general` | Yes | Yes | Internal |
| POST | `/content/general/toggle-show-properties` | Yes | Yes | Internal |
| GET | `/content/banner` | Yes | Yes | Internal |
| POST | `/content/banner` | Yes | Yes | Internal |
| GET | `/content/customer-dropdown` | Yes | Yes | Internal |
| PUT | `/content/customer-dropdown` | Yes | Yes | Internal |
| POST | `/content/customer-dropdown/toggle-visibility` | Yes | Yes | Internal |
| GET | `/content/about` | Yes | Yes | Internal |
| POST | `/content/about` | Yes | Yes | Internal |
| GET | `/content/menu` | Yes | Yes | Internal |
| PUT | `/content/menu` | Yes | Yes | Internal |
| GET | `/settings/theme` | Yes | Yes | Internal |
| POST | `/settings/theme/set-active` | Yes | Yes | Internal |
| POST | `/settings/theme/purchase` | Yes | Yes | Internal |
| GET | `/settings/payment` | Yes | Yes | Internal |
| GET | `/settings/domain` | Yes + can:settings.update | Yes | Internal |
| GET | `/settings/domain/{id}` | Yes + can:settings.update | Yes | Internal |
| POST | `/settings/domain` | Yes + can:settings.update | Yes | Internal |
| POST | `/settings/domain/verify` | Yes + can:settings.update | Yes | Internal |
| PATCH | `/settings/domain/set-primary` | Yes + can:settings.update | Yes | Internal |
| DELETE | `/settings/domain/{id}` | Yes + can:settings.update | Yes | Internal |
| PATCH | `/settings/domain/request-ssl` | Yes + can:settings.update | Yes | Internal |
| PATCH | `/settings/domain/ssl-status` | Yes + can:settings.update | Yes | Internal |
| GET | `/settings/side-menus` | Yes | Yes | Internal |
| GET | `user/categories` | Yes | Yes | Internal |
| PUT | `user/categories` | Yes | Yes | Internal |
| GET | `/user/cities` | Yes | Yes | Internal |
| GET | `/user/districts` | Yes | Yes | Internal |

---

## 15. Apps / installations

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/apps` | Yes | Yes | Internal |
| POST | `/apps/install` | Yes | Yes | Internal |
| POST | `/apps/uninstall/{appId}` | Yes | Yes | Internal |
| GET | `/apps/{appId}/purchase-url` | Yes | Yes | Internal |
| GET | `/apps/whatsapp` | Yes | Yes | Internal |
| POST | `/apps/whatsapp/install` | Yes | Yes | Internal |
| POST | `/apps/whatsapp/uninstall` | Yes | Yes | Internal |
| GET | `/installations/{installationId}/payment/status` | Yes | Yes | Internal |
| POST | `/apps/{appId}/payment/verify` | Yes | Yes | Internal |
| GET | `/apps/payments` | Yes | Yes | Internal |

---

## 16. Public templates & data

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `public-user/{id}` | No | No | Public |
| GET | `/properties/bulk-import/template` | No | No | Public |
| POST | `/customers/bulk-import/template` | No | No | Public |

---

## 17. Referrals

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/referrals` | No | No | Public |
| GET | `/referrals/{code}` | No | No | Public |

---

## 18. Analytics tracking (public)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/v1/analytics/page-view` | No | No | Public (throttle 100/1) |

---

## 19. Public admin articles

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/public/admin-article-categories` | No | No | Public |
| GET | `/public/admin-article-categories/{slug}/articles` | No | No | Public |
| GET | `/public/admin-articles` | No | No | Public |
| GET | `/public/admin-articles/{slug}` | No | No | Public |

---

## 20. Public support center

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/public/support-center/categories` | No | No | Public |
| GET | `/public/support-center/categories/{slug}/articles` | No | No | Public |
| GET | `/public/support-center/articles` | No | No | Public |
| GET | `/public/support-center/articles/{slug}` | No | No | Public |

---

## 21. Customers

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/customers/filters` | Yes + can:customers.view | Yes | Internal |
| GET | `/customers` | Yes + can:customers.view | Yes | Internal |
| GET | `/customers/all` | Yes + can:customers.view | Yes | Internal |
| GET | `/customers/search` | Yes + can:customers.view | Yes | Internal |
| GET | `/customers/export` | Yes + can:customers.view | Yes | Internal |
| POST | `/customers/bulk-import` | Yes + can:customers.create | Yes | Internal |
| GET | `/customers/{id}/with-inquiries` | Yes + can:customers.view | Yes | Internal |
| GET | `/customers/{id}` | Yes + can:customers.view | Yes | Internal |
| POST | `/customers` | Yes + can:customers.create | Yes | Internal |
| PUT | `/customers/{id}` | Yes + can:customers.update | Yes | Internal |
| DELETE | `/customers/{id}` | Yes + can:customers.delete | Yes | Internal |

---

## 22. CRM (stages, procedures, priorities, types, reminders, dashboard)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET/POST/PUT/PATCH/DELETE | `/crm/stages` (apiResource) | Yes + can:crm.view | Yes | Internal |
| POST | `/crm/stages/reorder` | Yes | Yes | Internal |
| POST | `/crm/stages/{id}/move` | Yes | Yes | Internal |
| (apiResource) | `/crm/procedures` | Yes | Yes | Internal |
| POST | `/crm/procedures/reorder`, `.../move` | Yes | Yes | Internal |
| (apiResource) | `/crm/priorities` | Yes | Yes | Internal |
| (apiResource) | `/crm/types` | Yes | Yes | Internal |
| (apiResource) | `/crm/customer-appointments` | Yes | Yes | Internal |
| GET | `/crm/customer-reminders/filter-options` | Yes | Yes | Internal |
| (apiResource) | `/crm/customer-reminders` | Yes | Yes | Internal |
| (apiResource) | `/crm/reminder-types` | Yes | Yes | Internal |
| (apiResource) | `/crm/reminders` | Yes | Yes | Internal |
| GET | `/crm` | Yes | Yes | Internal |
| GET | `/crm/customers/filters` | Yes | Yes | Internal |
| POST | `/crm/customers/{id}/change-stage` | Yes | Yes | Internal |
| POST | `/crm/customers/{id}/change-priority` | Yes | Yes | Internal |
| POST | `/crm/customers/{id}/change-type` | Yes | Yes | Internal |
| POST | `/crm/customers/{id}/change-procedure` | Yes | Yes | Internal |
| GET | `/crm/customers/search` | Yes | Yes | Internal |
| GET | `/crm/customers/export` | Yes + can:crm.view | Yes | Internal |
| GET | `/crm/customers/import/template` | Yes + can:crm.view | Yes | Internal |
| POST | `/crm/customers/import` | Yes + can:crm.create | Yes | Internal |
| GET | `/crm/property-requests/settings` | Yes | Yes | Internal |
| PUT | `/crm/property-requests/settings` | Yes | Yes | Internal |

---

## 23. Steps progress / embeddings / chat

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/steps/progress` | Yes | Yes | Internal |
| POST | `/steps/complete` | Yes | Yes | Internal |
| POST | `/embeddings` | Yes | Yes | Internal |
| POST | `/chat` | Yes | Yes | Internal |
| GET | `/whatsapp/meta/redirect` | Yes | Yes | Internal |

---

## 24. Payment / webhook callbacks (public, business-critical)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/apps/payment/callback/{gateway}` | No | No | Public |
| GET | `/v1/credits/packages` | No | No | Public |
| GET | `/v1/credits/payment/success/{transaction_id}/{gateway}` | No | No | Public |
| GET | `/v1/credits/payment/cancel/{transaction_id}/{gateway}` | No | No | Public |
| GET/POST | `/v1/whatsapp-addons/payment/success|cancel/{addon_id}/{gateway}` | No | No | Public |
| GET/POST | `/v1/employee-addons/payment/success|cancel/{addon_id}/{gateway}` | No | No | Public |
| GET/POST | `/themes/payment/success|cancel/{user_theme_id}/{gateway}` | No | No | Public |

---

## 25. WhatsApp / evolution (public callbacks)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/whatsapp/meta/callback` | No | No | Public |
| POST | `/whatsapp/evolution-webhook` | No | No | Public |
| POST | `/whatsapp/webhook` | No | No | Public |

---

## 26. Isthara / property requests (public)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/isthara` | No | No | Public |
| POST | `/v1/property-requests/public` | No | No | Public |

---

## 27. WhatsApp (requires active membership)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/whatsapp/link` | Yes + membership | Yes | Internal |
| GET | `/whatsapp` | Yes + membership | Yes | Internal |
| PUT/PATCH | `/whatsapp/{id}/employee` | Yes + membership | Yes | Internal |
| DELETE | `/whatsapp/{id}` | Yes + membership | Yes | Internal |
| POST | `/whatsapp/{id}/unlink` | Yes + membership | Yes | Internal |
| POST | `/whatsapp/{id}/link` | Yes + membership | Yes | Internal |
| GET | `/whatsapp/addons/plans` | Yes + membership | Yes | Internal |
| POST | `/whatsapp/addons` | Yes + membership | Yes | Internal |
| GET | `/employee/addons/plans` | Yes + membership | Yes | Internal |
| GET | `/employee/addons` | Yes + membership | Yes | Internal |
| POST | `/employee/addons` | Yes + membership | Yes | Internal |

---

## 28. V1 RMS (rentals, contracts, installments, maintenance, payments)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/rms/dashboard` | Yes | Yes | Internal |
| GET | `/v1/rms/payments/collections` | Yes | Yes | Internal |
| GET | `/v1/rms/payments/due` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/filter-options` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{id}` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{id}/details` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{id}/details-with-payments` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{id}/current-collections` | Yes | Yes | Internal |
| GET | `/v1/rms/payment-collection` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{id}/payment-collection` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{id}/collect-payment` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{id}/payments` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{rental}/payments/{payment}/reverse` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/upload-receipt-image` | Yes | Yes | Internal |
| PATCH | `/v1/rms/rentals/{id}` | Yes | Yes | Internal |
| PATCH | `/v1/rms/rentals/{id}/status` | Yes | Yes | Internal |
| DELETE | `/v1/rms/rentals/{id}` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{id}/end-contract` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{id}/renew` | Yes | Yes | Internal |
| POST | `/v1/rms/expenses/upload-image` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{rentalId}/expenses` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{rentalId}/expenses` | Yes | Yes | Internal |
| DELETE | `/v1/rms/rentals/{rentalId}/expenses/{expenseId}` | Yes | Yes | Internal |
| GET | `/v1/rms/payment-report` | Yes | Yes | Internal |
| GET | `/v1/rms/daily-follow-up` | Yes | Yes | Internal |
| GET | `/v1/rms/contracts` | Yes | Yes | Internal |
| GET | `/v1/rms/rentals/{rentalId}/contracts` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{rentalId}/contracts` | Yes | Yes | Internal |
| PATCH | `/v1/rms/contracts/{id}` | Yes | Yes | Internal |
| POST | `/v1/rms/contracts/{id}/terminate` | Yes | Yes | Internal |
| PATCH | `/v1/rms/contracts/{id}/status` | Yes | Yes | Internal |
| GET | `/v1/rms/installments` | Yes | Yes | Internal |
| PATCH | `/v1/rms/installments/{id}` | Yes | Yes | Internal |
| POST | `/v1/rms/rentals/{rentalId}/installments/regenerate` | Yes | Yes | Internal |
| GET | `/v1/rms/maintenance` | Yes | Yes | Internal |
| POST | `/v1/rms/maintenance` | Yes | Yes | Internal |
| GET | `/v1/rms/maintenance/{id}` | Yes | Yes | Internal |
| PATCH | `/v1/rms/maintenance/{id}` | Yes | Yes | Internal |
| POST | `/v1/rms/maintenance/{id}/status` | Yes | Yes | Internal |
| DELETE | `/v1/rms/maintenance/{id}` | Yes | Yes | Internal |
| GET | `/v1/rms/reminders` | Yes | Yes | Internal |
| POST | `/v1/rms/reminders/{id}/dismiss` | Yes | Yes | Internal |
| POST | `/v1/rms/reminders/{id}/snooze` | Yes | Yes | Internal |

---

## 29. V1 PMS (purchase requests)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/pms/dashboard` | Yes | Yes | Internal |
| GET | `/v1/pms/properties` | Yes | Yes | Internal |
| GET | `/v1/pms/projects` | Yes | Yes | Internal |
| GET | `/v1/pms/staff` | Yes | Yes | Internal |
| GET | `/v1/pms/purchase-requests` | Yes | Yes | Internal |
| POST | `/v1/pms/purchase-requests` | Yes | Yes | Internal |
| GET | `/v1/pms/purchase-requests/{id}` | Yes | Yes | Internal |
| PATCH | `/v1/pms/purchase-requests/{id}` | Yes | Yes | Internal |
| DELETE | `/v1/pms/purchase-requests/{id}` | Yes | Yes | Internal |
| POST | `/v1/pms/purchase-requests/{id}/transition-stage` | Yes | Yes | Internal |
| POST | `/v1/pms/purchase-requests/{id}/simple-transition-stage` | Yes | Yes | Internal |
| GET | `/v1/pms/purchase-requests/{purchaseRequestId}/stages` | Yes | Yes | Internal |
| GET | `/v1/pms/purchase-requests/{purchaseRequestId}/stages/statistics` | Yes | Yes | Internal |
| GET | `/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}` | Yes | Yes | Internal |
| PATCH | `/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/status` | Yes | Yes | Internal |
| PATCH | `/v1/pms/purchase-requests/{purchaseRequestId}/stages/{stageId}/notes` | Yes | Yes | Internal |
| PATCH | `/v1/pms/purchase-requests/{purchaseRequestId}/stages/bulk-update` | Yes | Yes | Internal |
| POST | `.../stages/{stageId}/mark-completed` | Yes | Yes | Internal |
| POST | `.../stages/{stageId}/mark-in-progress` | Yes | Yes | Internal |
| POST | `.../stages/{stageId}/mark-pending` | Yes | Yes | Internal |

---

## 30. V1 Inquiry / property requests / property request settings

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/inquiry` | Yes | Yes | Internal |
| GET | `/v1/property-requests/filters` | Yes + can:properties.view | Yes | Internal |
| GET | `/v1/property-requests` | Yes + can:properties.view | Yes | Internal |
| GET | `/v1/property-requests/{id}` | Yes + can:properties.view | Yes | Internal |
| POST | `/v1/property-requests` | Yes + can:properties.view | Yes | Internal |
| DELETE | `/v1/property-requests/{id}` | Yes + can:properties.view | Yes | Internal |
| PUT | `/v1/property-requests/{id}` | Yes + can:properties.view | Yes | Internal |
| PUT | `/v1/property-requests/{id}/status` | Yes + can:properties.view | Yes | Internal |
| PUT | `/v1/property-requests/customer/{customerID}/employee` | Yes + can:properties.view | Yes | Internal |
| PUT | `/v1/property-requests/{id}/employee` | Yes + can:properties.view | Yes | Internal |
| GET | `/v1/property-request-settings/` | Yes | Yes | Internal |
| GET | `/v1/property-request-settings/defaults` | Yes | Yes | Internal |
| POST | `/v1/property-request-settings/bulk` | Yes | Yes | Internal |
| PUT | `/v1/property-request-settings/{field}` | Yes | Yes | Internal |
| POST | `/v1/property-request-settings/reset` | Yes | Yes | Internal |

---

## 31. V1 Employee API (employee.only)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/em/auth/me` | Yes + employee.only | Yes | Internal |
| POST | `/v1/em/auth/logout` | Yes + employee.only | Yes | Internal |
| (apiResource) | `/v1/em/customers` | Yes + employee.only | Yes | Internal |

---

## 32. V1 Logs

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/logs` | Yes + can:logs.read | Yes | Internal |

---

## 33. V1 CRM cards / requests

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/crm/cards` | Yes (log.employee) | Yes | Internal |
| POST | `/v1/crm/cards` | Yes | Yes | Internal |
| GET | `/v1/crm/cards/{id}` | Yes | Yes | Internal |
| PUT/PATCH | `/v1/crm/cards/{id}` | Yes | Yes | Internal |
| DELETE | `/v1/crm/cards/{id}` | Yes | Yes | Internal |
| (apiResource) | `/v1/crm/requests` | Yes | Yes | Internal |
| POST | `/v1/crm/requests/{id}/change-stage` | Yes | Yes | Internal |
| POST | `/v1/crm/requests/reorder` | Yes | Yes | Internal |
| GET | `/v1/crm/requests/{id}/details` | Yes | Yes | Internal |
| GET | `/v1/crm/stages` | Yes | Yes | Internal |

---

## 34. V1 Marketing channels

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/marketing/channels` | Yes | Yes | Internal |
| POST | `/v1/marketing/channels` | Yes | Yes | Internal |
| GET | `/v1/marketing/channels/types` | Yes | Yes | Internal |
| GET | `/v1/marketing/channels/usage` | Yes | Yes | Internal |
| GET | `/v1/marketing/channels/{id}` | Yes | Yes | Internal |
| PUT | `/v1/marketing/channels/{id}` | Yes | Yes | Internal |
| PATCH | `/v1/marketing/channels/{id}/status` | Yes | Yes | Internal |
| GET | `/v1/marketing/channels/{id}/statistics` | Yes | Yes | Internal |
| GET | `/v1/marketing/channels/{id}/stats` | Yes | Yes | Internal |
| POST | `/v1/marketing/channels/{id}/sync-verified` | Yes | Yes | Internal |
| POST | `/v1/marketing/channels/{id}/send-message` | Yes | Yes | Internal |
| POST | `/v1/marketing/channels/send-whatsapp-to-customer` | Yes | Yes | Internal |
| DELETE | `/v1/marketing/channels/{id}` | Yes | Yes | Internal |
| GET | `/v1/marketing/settings` | Yes | Yes | Internal |
| GET | `/v1/marketing/channels/{id}/settings` | Yes | Yes | Internal |
| PUT | `/v1/marketing/channels/{id}/settings` | Yes | Yes | Internal |
| PATCH | `/v1/marketing/channels/{id}/system-integrations` | Yes | Yes | Internal |
| POST | `/v1/marketing/webhooks/whatsapp` | No | No | Public |

---

## 35. V1 Credits (authenticated)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/credits/balance` | Yes | Yes | Internal |
| POST | `/v1/credits/purchase` | Yes | Yes | Internal |
| GET | `/v1/credits/transactions` | Yes | Yes | Internal |
| GET | `/v1/credits/analytics` | Yes | Yes | Internal |

---

## 36. Me abilities / RBAC

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/me/abilities` | Yes | Yes | Internal |
| GET | `/v1/rbac/perms/me` | Yes | Yes | Internal |
| GET | `/v1/rbac/roles` | Yes + can:settings.update | Yes | Internal |
| POST | `/v1/rbac/roles` | Yes + can:settings.update | Yes | Internal |
| PUT | `/v1/rbac/roles/{role}` | Yes + can:settings.update | Yes | Internal |
| DELETE | `/v1/rbac/roles/{role}` | Yes + can:settings.update | Yes | Internal |
| GET | `/v1/rbac/permissions` | Yes + can:settings.update | Yes | Internal |
| POST | `/v1/rbac/permissions` | Yes + can:settings.update | Yes | Internal |
| PUT | `/v1/rbac/permissions/{permission}` | Yes + can:settings.update | Yes | Internal |
| DELETE | `/v1/rbac/permissions/{permission}` | Yes + can:settings.update | Yes | Internal |
| GET | `/v1/rbac/employees-show-roles/{employee}/roles` | Yes + can:settings.update | Yes | Internal |
| GET | `/v1/rbac/show-employees-data/{employee}` | Yes + can:settings.update | Yes | Internal |
| POST | `/v1/rbac/employees-sync-perms/{employee}/perms` | Yes + can:settings.update | Yes | Internal |
| POST | `/v1/rbac/employees-sync-roles/{employee}/roles` | Yes + can:settings.update | Yes | Internal |

---

## 37. V1 Reservations / job applications / entity logs

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/reservations` | Yes | Yes | Internal |
| GET | `/v1/reservations/stats` | Yes | Yes | Internal |
| GET | `/v1/reservations/export/csv` | Yes | Yes | Internal |
| GET | `/v1/reservations/{id}` | Yes | Yes | Internal |
| POST | `/v1/reservations/{id}/accept` | Yes | Yes | Internal |
| POST | `/v1/reservations/{id}/reject` | Yes | Yes | Internal |
| POST | `/v1/reservations/bulk-action` | Yes | Yes | Internal |
| GET | `/v1/job-applications` | Yes | Yes | Internal |
| GET | `/v1/job-applications/{id}` | Yes | Yes | Internal |
| GET | `/v1/customers/{id}/logs` | Yes + can:projects.view | Yes | Internal |
| GET | `/v1/projects/{id}/logs` | Yes + can:projects.view | Yes | Internal |
| GET | `/v1/properties/{id}/logs` | Yes + can:properties.view | Yes | Internal |
| GET | `/v1/crm/cards/{id}/logs` | Yes + can:crm.cards.view | Yes | Internal |

---

## 38. V1 Employees / roles (tenant)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/employees/available-roles` | Yes | Yes | Internal |
| GET | `/v1/employees/available-permissions` | Yes | Yes | Internal |
| (apiResource) | `/v1/employees` | Yes | Yes | Internal |
| (apiResource) | `/v1/roles` | Yes | Yes | Internal |
| GET | `/v1/permissions` | Yes | Yes | Internal |
| POST | `/v1/permissions` | Yes | Yes | Internal |
| PUT | `/v1/permissions/{id}` | Yes | Yes | Internal |
| DELETE | `/v1/permissions/{id}` | Yes | Yes | Internal |

---

## 39. Pixels / video upload

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/pixels` | Yes | Yes | Internal |
| POST | `/pixels` | Yes | Yes | Internal |
| GET | `/pixels/{id}` | Yes | Yes | Internal |
| PUT | `/pixels/{id}` | Yes | Yes | Internal |
| DELETE | `/pixels/{id}` | Yes | Yes | Internal |
| PATCH | `/pixels/{id}/toggle-status` | Yes | Yes | Internal |
| POST | `/video/upload` | Yes | Yes | Internal |
| POST | `/video/initiate-chunked` | Yes | Yes | Internal |
| POST | `/video/upload-chunk` | Yes | Yes | Internal |
| POST | `/video/complete-chunked` | Yes | Yes | Internal |
| POST | `/video/abort-chunked` | Yes | Yes | Internal |
| POST | `/video/signed-url` | Yes | Yes | Internal |
| DELETE | `/video/delete` | Yes | Yes | Internal |

---

## 40. Tenant website (mixed public / auth)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/v1/tenant-website/getTenant` | No | No (resolve tenant) | Public |
| POST | `/v1/tenant-website/save-pages` | Yes | Yes | Internal |
| GET | `/v1/tenant-website/{tenantId}/pixels` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}` | No | No | Public (search) |
| GET | `/v1/tenant-website/{tenantId}/pages` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/pages/{pageId}` | No | No | Public |
| POST | `/v1/tenant-website/{tenantId}/pages` | Yes | Yes | Internal |
| PUT | `/v1/tenant-website/{tenantId}/pages/{pageId}` | Yes | Yes | Internal |
| PATCH | `/v1/tenant-website/{tenantId}/pages/{pageId}` | Yes | Yes | Internal |
| DELETE | `/v1/tenant-website/{tenantId}/pages/{pageId}` | Yes | Yes | Internal |
| PUT | `/v1/tenant-website/{tenantId}/globals` | Yes | Yes | Internal |
| GET | `/v1/tenant-website/components/catalog` | No | No | Public |
| POST | `/v1/tenant-website/{tenantId}/media` | Yes | Yes | Internal |
| PUT | `/v1/tenant-website/{tenantId}/settings` | Yes | Yes | Internal |
| POST | `/v1/tenant-website/{tenantId}/publish` | Yes | Yes | Internal |
| POST | `/v1/tenant-website/{tenantId}/forms/contact` | No | No | Public |
| POST | `/v1/tenant-website/{tenantId}/reservations` | No | No | Public (throttle 5/1) |
| POST | `/v1/tenant-website/{tenantId}/job-applications` | No | No | Public (throttle 10/1) |
| GET | `/v1/tenant-website/{tenantId}/properties` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/properties/{slug}` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/projects` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/projects/{slug}` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/posts` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/posts/{slug}` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/ai-export` | No | No | Public |
| GET | `/v1/tenant-website/{tenantId}/ai-export.txt` | No | No | Public |

---

## 41. Property categories (direct public)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/tenant-website/{tenantId}/properties/categories/direct` | No | No | Public |

---

## 42. Matching (customer requests)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/matching/requests` | Yes | Yes | Internal |
| GET | `/v1/matching/requests/{type}/{id}` | Yes | Yes | Internal |
| PUT | `/v1/matching/requests/{type}/{id}` | Yes | Yes | Internal |
| PATCH | `/v1/matching/requests/{type}/{id}/read` | Yes | Yes | Internal |
| PATCH | `/v1/matching/requests/{type}/{id}/unread` | Yes | Yes | Internal |
| PATCH | `/v1/matching/requests/{type}/{id}/archive` | Yes | Yes | Internal |
| PATCH | `/v1/matching/requests/{type}/{id}/unarchive` | Yes | Yes | Internal |
| GET | `/v1/matching/customers` | Yes | Yes | Internal |
| GET | `/v1/matching/customers/{customer_key}/properties` | Yes | Yes | Internal |
| GET | `/v1/matching/matches/{id}` | Yes | Yes | Internal |

---

## 43. Owner rental management (user dashboard)

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| GET | `/v1/user/owner-rentals` | Yes | Yes | Internal |
| POST | `/v1/user/owner-rentals` | Yes | Yes | Internal |
| GET | `/v1/user/owner-rentals/{id}` | Yes | Yes | Internal |
| PUT | `/v1/user/owner-rentals/{id}` | Yes | Yes | Internal |
| DELETE | `/v1/user/owner-rentals/{id}` | Yes | Yes | Internal |
| POST | `/v1/user/owner-rentals/{id}/properties` | Yes | Yes | Internal |
| DELETE | `/v1/user/owner-rentals/{id}/properties/{propertyId}` | Yes | Yes | Internal |
| GET | `/v1/user/owner-rentals/{id}/properties` | Yes | Yes | Internal |
| GET | `/v1/user/properties` | Yes | Yes | Internal |

---

## 44. Owner rental auth & portal

| Method | Endpoint | Auth | Tenant-scoped | Visibility |
|--------|----------|------|---------------|------------|
| POST | `/v1/owner-rental/login` | No | No | Public |
| POST | `/v1/owner-rental/forgot-password` | No | No | Public |
| POST | `/v1/owner-rental/reset-password` | No | No | Public |
| POST | `/v1/owner-rental/logout` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/me` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/dashboard` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/check-property/{id}` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/properties` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/properties/{id}` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/rentals` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/tenants` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/financial-reports` | Yes (owner-rental token) | Yes | Internal |
| GET | `/v1/owner-rental/maintenance-requests` | Yes (owner-rental token) | Yes | Internal |

---

**Excluded from this inventory**

- Debug routes (e.g. `/debug-oss`, `/dashboard/debug-ga-views`, `/dashboard/diagnostic-ga-test`, `/analytics/live-test`, `/analytics/tenants`).
- Health checks (none found in `routes/api.php`).
- Third-party callbacks not listed above (only business-critical payment/success|cancel and WhatsApp webhooks included).
