# Testing Setup – AI Agent Instructions

Use this when writing or running tests for this project.

<!-- E2E strategy LOCK: Assume schema exists; never run migrations. Restore from dump only. Do not add RefreshDatabase or migrate/migrate:fresh to E2E. -->

---

## Test Database (Not Production)

- **Database:** `taearif_testing` (MySQL).
- **Schema source:** Restore from **`the_test_db/taearif_testing.sql`** into `taearif_testing`. Do **not** rely on `migrate:fresh` to build the full schema; many tables (e.g. `users`, `memberships`, `user_basic_settings`, `user_vcards`) come from this dump or from package/core, not from app migrations.
- **E2E tests:** Assume schema exists; **never run migrations** for E2E. Restore the dump before tests. Do not use `RefreshDatabase` or `migrate`/`migrate:fresh` in E2E.

---

## How to Run Tests

1. **Restore the test DB** when needed (e.g. after schema changes or first run):
   - Windows (cmd): `cd D:\laragon\www\taearif && cmd /c "mysql -u root taearif_testing < the_test_db/taearif_testing.sql"`
   - Or equivalent for your OS/DB (PowerShell may need different quoting).
2. **Unit tests:**  
   `php artisan test tests/Unit` or `php artisan test tests/Unit/RentalControllerTest.php`
3. **API-related feature tests** (excludes Admin and web-only):  
   `php artisan test --testsuite=FeatureApi`

---

## Test Traits to Use

- **Feature / API tests:** Use **`DatabaseTransactions`** (wrap each test in a transaction, roll back after). Do **not** use **`RefreshDatabase`** for tests that need the full schema; it runs `migrate:fresh`, wipes the dump, and migrations then fail because base tables are missing.
- **Unit tests:** No DB trait needed when services are mocked. If a test hits the DB, use **`DatabaseTransactions`** so it runs against the restored `taearif_testing` schema.

---

## When Adding or Changing Tests

- **E2E / API feature lock:** Assume schema exists; **never run migrations**. Restore from dump only; do not use `RefreshDatabase`, `migrate`, or `migrate:fresh` for E2E.
- Use the **test** database and the dump as the source of truth for schema.
- Do **not** assume `migrate:fresh` creates all tables; assume the DB is already restored from `the_test_db/taearif_testing.sql` and use **`DatabaseTransactions`** for isolation.
- For API (routes/api.php) work: ignore or exclude Admin-api and web-only tests; use the **FeatureApi** suite.

---

## Quick Reference

| Task              | Command / Trait                          |
|-------------------|------------------------------------------|
| Restore test DB   | `mysql -u root taearif_testing < the_test_db/taearif_testing.sql` |
| Unit tests        | `php artisan test tests/Unit`            |
| API feature tests | `php artisan test --testsuite=FeatureApi` |
| Feature tests     | Use `DatabaseTransactions`, not `RefreshDatabase` |

---

## Prompt: Ask the Agent to Make Tests

Copy-paste this when you want the agent to **write or add tests**:

```
Make tests for [describe what to test: e.g. "the new reservation API endpoint", "RentalService::createRental", "the tenant website contact form"].

Follow the testing setup in tests/TESTING_PROMPT.md:
- Test database is taearif_testing; schema comes from the_test_db/taearif_testing.sql (restore that dump when needed). **E2E strategy lock:** Assume schema exists; never run migrations. Do not use RefreshDatabase or migrate/migrate:fresh for E2E or API feature tests.
- For feature/API tests: use DatabaseTransactions, not RefreshDatabase. Do not use migrate or migrate:fresh.
- For API (routes/api.php) tests: put them in tests/Feature and add the file to the FeatureApi suite in phpunit.xml if it's not already there; run with: php artisan test --testsuite=FeatureApi.
- For E2E tests: extend ApiE2ETestCase; restore DB from dump before running; never run migrations.
- For unit tests: put them in tests/Unit; mock services where appropriate; run with: php artisan test tests/Unit.
- Use Pest or PHPUnit conventions already used in this project (check existing tests in tests/Feature and tests/Unit).
- After writing tests, run them and fix any failures.
```
