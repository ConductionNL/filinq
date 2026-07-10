# Tasks: fix-dossier-grondslagen-route-mismatch

All tasks are `[docudesk]`. Estimates: S = half-day.

## [docudesk] Route correction

### R-1. Fix the route-to-method binding (S)

- [ ] R-1.1 In `appinfo/routes.php`, change the route entry name from
  `'dossier#generateGrondslagenPdf'` to `'dossier#generateGrondslagenSummary'`
  (URL `api/anonymization/dossier/{dossierId}/grondslagen-pdf` and verb `POST`
  stay unchanged).
  - **Acceptance:** `grep -n "generateGrondslagenSummary" appinfo/routes.php`
    finds the entry; no route entry names `generateGrondslagenPdf` anywhere in
    the repo.
- [ ] R-1.2 Run `hydra-gate-route-reachability` (or the equivalent manual
  check: confirm `DossierController` exposes a public method matching every
  route entry naming it) and confirm it passes for `dossier#*`.

### R-2. Regression coverage across the HTTP boundary (S)

- [ ] R-2.1 Add (or extend) a Newman/Postman case under `tests/integration/`
  that POSTs `api/anonymization/dossier/{dossierId}/grondslagen-pdf` against a
  running instance and asserts a non-500 response, per ADR-008 + ADR-029
  Invariant 3. This closes the gap that let the mismatch ship silently behind
  green in-process unit tests.
  - **Acceptance:** The Newman collection includes the case and it passes
    against a seeded dossier fixture.
- [ ] R-2.2 Confirm `tests/unit/Controller/DossierControllerTest.php` still
  passes unchanged (it already calls `generateGrondslagenSummary()` directly
  and needs no edits).

### R-3. Verify the real click path (S)

- [ ] R-3.1 Manually (or via Playwright) click "Append a grondslagen-summary
  page to each anonymised PDF (Wave 4a)" in `FolderAnonymizationView.vue` on a
  running dev instance and confirm the PDF regenerates instead of the request
  500ing.
