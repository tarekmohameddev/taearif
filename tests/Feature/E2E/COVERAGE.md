# E2E Coverage (Single Source of Truth)

**Database strategy:** Schema and base data come from a restored SQL dump. **Never** run migrations or `migrate:fresh`. Use **DatabaseTransactions** for isolation. Some tests are intentionally skipped due to known E2E limitations (documented in `KNOWN_BACKEND_BUGS.md`), not app bugs.

---

## Coverage Table

| # | Business flow | Test file | Status | Notes |
|---|----------------|-----------|--------|--------|
| 1 | User Registration & Login | `UserRegistrationLoginTest.php` | ⏭ Partially covered | Full journey: register → login → GET /user → logout → GET /user (expect 401). Can skip: (a) setup (package 26, default language, DB); (b) **post-logout 401** — logout revocation works in production; E2E cannot reliably assert 401 after logout (DB visibility / connection). See KNOWN_BACKEND_BUGS § Known E2E Limitations. Three tests always run: protected_route_401, login_invalid_credentials_401, logout_without_token_401. |
| 2 | Owner-Rental Portal | `OwnerRentalPortalTest.php` | ⏭ Partially covered | Full journey: login → me → dashboard → properties → rentals → logout. Skips when `owner_rentals` table or env missing, or any step returns non-200. Invalid-credentials test runs when no skip. Schema-dependent. |
| 3 | RMS: Create Rental & Collect Payment | `RmsCreateRentalCollectPaymentTest.php` | ⚠️ Partially covered | Full journey: login → filter-options → POST rentals → details-with-payments → **collect-payment** → GET payments. Skips when **collect-payment returns non-200** (e.g. 500 — may require payment config or installments). See KNOWN_BACKEND_BUGS § Known Blocking API Bugs. |
| 4 | Customer & CRM Stage Change | `CustomerCrmStageChangeTest.php` | ✅ Fully covered | Login → POST customers → GET crm → POST change-stage → GET customers/{id}. No skips. Requires `users_api_customers_types`, `users_api_customers_stages` (created in test if missing). |
| 5 | Property Request (public) → Dashboard | `PropertyRequestPublicDashboardTest.php` | ✅ Fully covered | POST public → login → GET property-requests → PUT status (if status exists) → PUT employee (if employee exists). Requires `user_cities` row. |
| 6 | Tenant Website: Public Reservation | `TenantWebsitePublicReservationTest.php` | ⏭ Partially covered | getTenant → GET properties → POST reservation → login → GET reservations → accept. Skips when getTenant or reservations list returns non-200 (tenant-website API / basic_settings setup). |
| 7 | Credits (packages + balance) | `CreditsPackagesTest.php` | ✅ Fully covered | GET packages (200 or 400 accepted) → login → GET balance. No skips; asserts balance 200. Requires schema with users + login. |
| 8 | Employee login under tenant | `EmployeeLoginUnderTenantTest.php` | ✅ Fully covered | POST /api/login as employee (tenant_id set). Happy: employee with active tenant → 200, user + token. Failure: employee with inactive tenant → 403, "Tenant is inactive; employee login disabled". Skips only if users table or required columns missing (QueryException). |
| 9 | Permission-restricted API access | `PermissionRestrictedApiAccessTest.php` | ✅ Fully covered | Employee without required permission → 403; same employee with required permission → 200. Uses GET /api/v1/projects/{id}/logs (can:projects.view). Skips only if required RBAC/log tables are missing. |
| 10 | Dashboard access with package enforcement | `DashboardRequiresActivePackageTest.php` | ✅ Fully covered | Tenant user with inactive/expired package → 402 "No active package."; same user with active package → 200. Uses GET /api/dashboard (require.active.package middleware). Skips only if users or memberships table missing. |
| 11 | RBAC role assignment at login time | `RbacRoleAssignmentAtLoginTest.php` | ✅ Fully covered | User without role → 403 on role-protected endpoint; same user assigned role (project-viewer with projects.view permission) → login again (new token) → 200. Uses GET /api/v1/projects/{id}/logs. Tests that roles grant permissions evaluated at authentication time. Skips only if RBAC tables (api_roles, api_role_has_permissions) missing. |
| 12 | Projects CRUD | `ProjectsCrudTest.php` | ✅ Fully covered | Tenant user with active package + default language + project permissions (projects.create/view/update/delete) → login → POST /api/projects (201/200) → GET /api/projects (list) → GET /api/projects/{id} (200) → POST /api/projects/{id} (update, 200) → DELETE /api/projects/{id} (200/204) → GET /api/projects/{id} (403/404). Full CRUD flow with RBAC + package enforcement. Skips only if user_projects, user_languages, memberships, packages, or RBAC tables missing. |
| 13 | Properties CRUD | `PropertiesCrudTest.php` | ✅ Fully covered | Tenant user with active package + default language + property permissions (properties.create/view/update/delete) → login → POST /api/properties (201/200) → GET /api/properties (list) → GET /api/properties/{id} (200) → POST /api/properties/{id} (update, 200) → DELETE /api/properties/{id} (200/204) → GET /api/properties/{id} (403/404). Full CRUD flow with RBAC + package enforcement. Skips only if user_properties, user_languages, memberships, packages, or RBAC tables missing. |
| 14 | Employee CRUD | `EmployeeCrudTest.php` | ✅ Fully covered | Tenant user with active package + default language + employee permissions (employees.create/view/update/delete) → login → POST /api/v1/employees (201/200) → GET /api/v1/employees (list) → GET /api/v1/employees/{id} (200) → PUT /api/v1/employees/{id} (update, 200) → DELETE /api/v1/employees/{id} (200/204) → GET /api/v1/employees/{id} (403/404). Full CRUD flow with RBAC + package enforcement. Skips only if users, user_languages, memberships, packages, or RBAC tables missing. |

---

## Missing Flows (Proposed Rows Only — No Tests Yet)

| # | Business flow | Proposed test file | Reason to add |
|---|----------------|--------------------|----------------|
| 15 | Password Reset | `PasswordResetTest.php` | Flow: forgot-password → verify-reset-code; auth edge case. |
| 16 | Tenant onboarding completion | `OnboardingFirstPaymentTest.php` | Flow: register → GET /user → POST onboarding → POST make-payment; first-time tenant journey. |

Other flows from `api-business-flows.md` not yet in this table: Draft Property Completion, RMS Contract & Installments, Rental Contract (legacy) Terminate, Blog/Post, Affiliate Registration, Impersonation, V1 RBAC Assign Role, Matching Customer Requests, Apps & WhatsApp Install — may be added as rows when E2E is extended.

---

## Summary

- **Total flows in coverage table:** 14
- **✅ Fully covered:** 10 (Customer & CRM Stage Change, Property Request Public → Dashboard, Credits, Employee login under tenant, Permission-restricted API access, Dashboard access with package enforcement, RBAC role assignment at login time, Projects CRUD, Properties CRUD, Employee CRUD)
- **⚠️ Partially covered (backend/API):** 1 (RMS Create Rental & Collect Payment — collect-payment returns 500; see KNOWN_BACKEND_BUGS)
- **⏭ Partially covered (skips / E2E limitations):** 3 (User Registration & Login — logout token visibility + setup; Owner-Rental Portal — schema; Tenant Website Public Reservation — setup)  
- **❌ Missing (no test):** 0 for flows in table; **2 proposed** new flows above (rows only, no tests yet).

Do not weaken assertions, fake logout/auth, or convert skips to passes unless the real cause (backend fix or env) is addressed.
