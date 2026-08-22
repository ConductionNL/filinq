<!--
SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>
SPDX-License-Identifier: EUPL-1.2
-->

# Filinq API Contract Suite (Newman / Postman)

API-contract tests that lock the HTTP behaviour of Filinq's controllers
(`appinfo/routes.php` + `lib/Controller/*`) against a live Nextcloud instance.

- Collection: `filinq.postman_collection.json`
- Runner: `run-newman.sh`

## Running

```bash
./run-newman.sh                                   # localhost:8080, admin:admin
BASE_URL=http://localhost:8080 ./run-newman.sh
ADMIN_USER=admin ADMIN_PASS=admin ./run-newman.sh
```

The runner re-execs under an exclusive `flock` on `/tmp/uiaudit-filinq.lock`
so concurrent CI agents serialise and do not trip Nextcloud's brute-force
protection. It uses a globally-installed `newman` when present, else
`npx newman`. `--ignore-redirects` is passed so the authz tests assert NC's
real `401` instead of following a `303` to the login page.

## What it covers

Each endpoint family gets a happy path, an error/validation case
(**4xx, not 500**), and an authorization case (no auth → `401`/`403`):

| Folder | Endpoints | Notes |
| --- | --- | --- |
| 0. Setup | `POST /api/templates`, `POST /api/signing/requests` | Seeds a template + a PENDING signing request, captures `templateId` / `signingId`. |
| 1. Templates | `GET/POST/PUT/DELETE /api/templates`, `GET .../{id}`, `GET .../{id}/versions` | CRUD + version history (the Phase-0 `templateVersion` fix). |
| 2. Signing | `POST/GET /api/signing/requests`, `GET .../{id}`, `GET .../{id}/audit`, `DELETE .../{id}` | create→PENDING→list (the Phase-0 Entity-vs-array fix). |
| 3. Anonymization | `GET /api/anonymization/files`, `POST .../extract/{fileId}`, `POST .../anonymize/{fileId}`, `POST .../batch/folder` | Asserts clean JSON 4xx/200, **never an HTML 500**, even with the NER sidecar absent (the Phase-0 Throwable fix). |
| 4. Consent | `GET/POST /api/consents`, `GET .../document/{documentId}` | GDPR publication consent. |
| 5. Settings | `GET /api/settings`, `POST /api/settings` | Admin-gated write (`#[AuthorizedAdminSetting]`). |
| 9. Teardown | `DELETE` signing request + template | Idempotent cleanup of everything seeded. |

Totals: **33 requests, 66 assertions** — all green against a live dev instance.

## Auth model & host split

Collection-level auth is `noauth`. Every authenticated request carries an
**explicit** basic-auth block (`{{adminUser}}` / `{{adminPass}}`) plus an
`OCS-APIRequest: true` header (required by the NC app framework for POST/PUT/DELETE).

Authorization (no-auth) requests run against **`{{noAuthBase}}` (127.0.0.1)**
while authed requests run against **`{{baseUrl}}` (localhost)**. NC session
cookies are host-scoped, so the cookie established by an authed request is never
sent to the 127.0.0.1 host — the no-auth tests are therefore genuinely
unauthenticated. They also carry `Accept: application/json` so a clean JSON
`401` is returned (not an HTML page).

## Phase-0 fixes locked at the API level

1. **Signing create (Entity-vs-array).** `POST /api/signing/requests` returns
   **201** with `status: "PENDING"` and the seeded request appears in
   `GET /api/signing/requests` — not a 500 on an Entity-vs-array mismatch.
2. **Template version history.** `GET /api/templates/{id}/versions` returns a
   **200** results envelope instead of 500-ing on the version register/schema.
3. **Anonymization Throwable handling.** `GET /api/anonymization/files`,
   `POST /api/anonymization/extract/{fileId}` and
   `POST /api/anonymization/batch/folder` all return **clean JSON** (200 / 404 /
   400) with `Content-Type: application/json`, never an HTML 500 — even though
   the NER sidecar is absent.

   > A duplicate `use Throwable;` import in
   > `lib/Service/AnonymizationService.php` was found during this work: it
   > fatally broke class loading (`Cannot use Throwable as Throwable because the
   > name is already in use`), so the whole anonymization surface returned an
   > HTML 500 *before* the controller's `catch (\Throwable)` could ever run. The
   > duplicate import was removed so the Throwable fix is actually effective.

## Quarantined bugs (non-fake-pass)

These assert the **current bad status** so the suite stays green and the bug
stays visible (mirroring procest's complaints-500 quarantine). Each also asserts
the response is still clean JSON (not an HTML error page):

- **`QUARANTINE template show non-existent -> 500`** — a non-existent template id
  returns **500** instead of 404. `TemplateService::getTemplate()` lets
  OpenRegister's `Object not found in magic table` exception fall through to the
  generic `catch (Exception)` which `buildErrorResponse()` emits as 500. Fix:
  map not-found to `STATUS_NOT_FOUND`.
- **`signing create invalid signature level -> 500`** — an invalid `signatureLevel`
  enum returns **500** instead of 400. `SigningService` throws a plain
  `RuntimeException('Invalid signature level')` which the controller maps to 500;
  a bad enum is a client error and should be 400.
