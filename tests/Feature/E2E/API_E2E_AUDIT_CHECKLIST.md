# API E2E Audit Checklist

**Scope:** `routes/api.php` and referenced controllers. Multi-tenant, single-database. API-only; admin-only and web routes ignored.  
**E2E rules:** API only, DatabaseTransactions, no migrate/RefreshDatabase, DB from dump.  
**Source of truth for coverage:** `tests/Feature/E2E/COVERAGE.md`.

---

## Legend

| Symbol | Meaning |
|-------|--------|
| ✅ | Flow is **E2E tested** (fully covered per COVERAGE.md) |
| ⏭ | Flow is **partially tested** (test exists; skips or known backend/E2E limitations) |
| ❌ | **Not tested** (no E2E test for this flow) |

---

## 1. Authentication (tenant / user)

| Status | Flow | Description |
|--------|------|-------------|
| ⏭ | **Auth: register, login, logout, password** | Register, login, GET /user, logout; forgot-password, verify-reset-code. Post-logout 401 not asserted (E2E limitation). |

---

## 2. User profile & session

| Status | Flow | Description |
|--------|------|-------------|
| ⏭ | **User profile** | GET /user, getUserInfo, user-read-message. Covered as part of User Registration & Login. |

---

## 3. Owner-rental portal (public auth + protected dashboard)

| Status | Flow | Description |
|--------|------|-------------|
| ⏭ | **Owner-rental auth & portal** | Owner-rental login, logout, me, dashboard, properties, rentals, tenants, financial-reports, maintenance-requests. Schema-dependent skips. |

---

## 4. Tenant onboarding & payments

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Payments / checkout / onboarding** | make-payment, make-payment-app, POST onboarding. First-time tenant journey not E2E covered. |
| ❌ | **Dashboard (require active package)** | Dashboard, summary, visitors, devices, traffic-sources, setup-progress, recent-activity; require.active.package. No test for 403 without package. |

---

## 5. Analytics & tracking

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Analytics (v1, auth)** | v1/analytics/dashboard, top-pages, top-posts, views-summary; v1/analytics/ga4/*. |
| ❌ | **Analytics tracking (public)** | POST v1/analytics/page-view (throttled). |

---

## 6. Content & blog

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Blog (legacy)** | CRUD blogs, blog-categories, upload-image. |
| ❌ | **Blog posts / categories** | Posts, categories, media CRUD by slug. |

---

## 7. Contracts (legacy + RMS)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Contracts** | contracts index/show/store/update/destroy, statistics, by customer/rental. |
| ❌ | **Rental contracts (legacy)** | rental-contracts CRUD, statistics, daily-follow-up, filter, terminate, status. |

---

## 8. Projects & properties (tenant)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Projects** | projects CRUD, user/projects, toggle-featured (can:projects.*). |
| ❌ | **Properties** | properties CRUD, drafts, bulk-import, filter-options, cards, buildings, facades, FAQs. |
| ❌ | **Owner rental management (user dashboard)** | v1/user/owner-rentals CRUD, assign/remove properties; v1/user/properties. |

---

## 9. Content, upload, regions, settings (tenant)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Content / upload / regions / settings** | content sections, footer, general, banner, menu, about; upload/delete-file; regions; theme, payment, domain, side-menus; user categories, cities, districts. |

---

## 10. Apps & installations

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Apps / installations** | apps index, install, uninstall, purchase-url; whatsapp install/uninstall; installation payment status/verify; apps/payments. |

---

## 11. Public templates & referrals

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Public templates & data** | public-user/{id}, properties/customers bulk-import template. |
| ❌ | **Referrals** | GET referrals, referrals/{code} (validate/show). |

---

## 12. Public content (no auth)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Public admin articles** | public/admin-article-categories, admin-articles. |
| ❌ | **Public support center** | public/support-center/categories, articles. |

---

## 13. Customers & CRM (tenant)

| Status | Flow | Description |
|--------|------|-------------|
| ✅ | **Customers & CRM stage change** | customers CRUD, filters, bulk-import; CRM dashboard, change-stage/priority/type/procedure, search, export/import; stages, procedures, priorities, types, reminders, appointments; property-request settings. |

---

## 14. Steps, embeddings, chat & WhatsApp (tenant)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Steps progress / embeddings / chat** | steps/progress, complete; embeddings, chat; whatsapp/meta/redirect. |
| ❌ | **WhatsApp (requires active membership)** | whatsapp link, index, updateEmployee, destroy, unlink/link; whatsapp/employee addons plans + CRUD. |
| ❌ | **WhatsApp / payment callbacks (public)** | whatsapp meta callback, evolution-webhook, webhook; credits/whatsapp/employee-addons/themes payment success|cancel. |

---

## 15. RMS (v1 rentals, contracts, installments, maintenance)

| Status | Flow | Description |
|--------|------|-------------|
| ⏭ | **V1 RMS** | dashboard, rentals CRUD, payment-collection, collect-payment, payments, reverse; expenses; contracts, installments, maintenance, reminders; daily-follow-up, payment-report. Partially covered; collect-payment returns 500 in current env (KNOWN_BACKEND_BUGS). |

---

## 16. PMS (purchase requests)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **V1 PMS** | purchase-requests CRUD, transition-stage; stages index/statistics/show, status/notes/bulk, mark-completed|in-progress|pending; dashboard, properties, projects, staff. |

---

## 17. Property requests (public + dashboard)

| Status | Flow | Description |
|--------|------|-------------|
| ✅ | **Property request (public) → dashboard** | POST v1/property-requests/public; GET/PUT property-requests, status, employee; property-request-settings. |

---

## 18. Isthara (public)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Isthara** | POST /isthara (public). |

---

## 19. Credits (public packages + authenticated balance/purchase)

| Status | Flow | Description |
|--------|------|-------------|
| ✅ | **Credits (packages + balance)** | GET v1/credits/packages (public); GET balance, POST purchase, GET transactions/analytics (auth). |

---

## 20. Employee API (v1, employee.only)

| Status | Flow | Description |
|--------|------|-------------|
| ✅ | **Employee login under tenant** | Employee login (tenant_id); me, logout; customers apiResource. Active vs inactive tenant (403) covered. |

---

## 21. V1 Inquiry, logs, CRM cards/requests

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **V1 Inquiry** | GET v1/inquiry. |
| ❌ | **V1 Logs** | GET v1/logs (can:logs.read). |
| ❌ | **V1 CRM cards / requests** | crm/cards CRUD; crm/requests apiResource, change-stage, reorder, details; crm/stages. |

---

## 22. Marketing & pixels

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **V1 Marketing channels** | channels CRUD, types, usage, statistics, send-message, settings, system-integrations; marketing/webhooks/whatsapp. |
| ❌ | **Pixels / video upload** | pixels CRUD, toggle-status; video upload (chunked, signed-url, delete). |

---

## 23. RBAC & permissions (tenant)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Me abilities / RBAC** | v1/me/abilities, rbac/perms/me; rbac roles/permissions CRUD; employees-show-roles, showEmployeeData, syncPerms, syncRoles. |
| ❌ | **V1 Employees / roles** | employees apiResource, available-roles, available-permissions; roles apiResource, permissions CRUD. |

---

## 24. Reservations & job applications (tenant)

| Status | Flow | Description |
|--------|------|-------------|
| ⏭ | **V1 Reservations** | reservations index, stats, export, show, accept, reject, bulk-action. Partially covered via Tenant Website Public Reservation (submit → list → accept). |
| ❌ | **V1 Job applications** | GET job-applications index/show. |
| ❌ | **Entity logs** | customers/projects/properties/crm/cards logs. |

---

## 25. Tenant website (mixed public / auth)

| Status | Flow | Description |
|--------|------|-------------|
| ⏭ | **Tenant website** | getTenant, pages, globals, media, settings, publish; public: pixels, search, properties, projects, posts, reservations, job-applications, forms/contact, ai-export. Partially covered: getTenant → properties → reservation → accept. |

---

## 26. Matching (customer requests)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Matching** | matching/requests index/show/update, read/unread, archive/unarchive; matching/customers, customer properties; matches/show. |

---

## 27. Affiliate & impersonation

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Affiliate** | POST affiliate/register, GET affiliate. |
| ❌ | **Impersonation** | POST impersonate/{user}, impersonate/{user}/revoke. |

---

## 28. Payment / app callbacks (public)

| Status | Flow | Description |
|--------|------|-------------|
| ❌ | **Payment / webhook callbacks** | apps/payment/callback/{gateway}; credits/whatsapp-addons/employee-addons/themes payment success|cancel (business-critical; no E2E). |

---

## Summary counts

| Status | Count |
|--------|-------|
| ✅ E2E tested | 4 |
| ⏭ Partially tested | 5 |
| ❌ Not tested | 24+ (grouped flows) |

**Fully covered flows:** Customers & CRM stage change, Property request (public) → dashboard, Credits (packages + balance), Employee login under tenant.  
**Partially covered:** User registration & login (incl. profile), Owner-rental portal, V1 RMS (collect-payment bug), V1 Reservations (via tenant-website test), Tenant website.  
**Not tested:** All other flows above (onboarding, dashboard/package, analytics, blog, contracts, projects, properties, content/settings, apps, referrals, public content, steps/chat/WhatsApp, PMS, Isthara, inquiry, logs, CRM cards/requests, marketing, pixels/video, RBAC, employees/roles, job applications, entity logs, matching, affiliate, impersonation, payment callbacks).
