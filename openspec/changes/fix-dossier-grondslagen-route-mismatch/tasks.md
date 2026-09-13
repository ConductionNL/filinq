# Tasks: fix-dossier-grondslagen-route-mismatch

> ✅ ALREADY IN THE CODE, VERIFIED 2026-09-07. The route correction had shipped
> at some point without these boxes being ticked, so the change read as
> not-started while the defect was gone. Verified now: `appinfo/routes.php` names
> `dossier#generateGrondslagenSummary`, `DossierController` exposes that method,
> no route entry names `generateGrondslagenPdf` anywhere in the repo, and
> `DossierControllerTest` passes. R-2.1's Newman case is the one part still
> outstanding.

All tasks are `[filinq]`. Estimates: S = half-day.

## [filinq] Route correction

### R-1. Fix the route-to-method binding (S)

- [x] R-1.1 In `appinfo/routes.php`, change the route entry name from
  `'dossier#generateGrondslagenPdf'` to `'dossier#generateGrondslagenSummary'`
  (URL `api/anonymization/dossier/{dossierId}/grondslagen-pdf` and verb `POST`
  stay unchanged).
  - **Acceptance:** `grep -n "generateGrondslagenSummary" appinfo/routes.php`
    finds the entry; no route entry names `generateGrondslagenPdf` anywhere in
    the repo.
- [x] R-1.2 Run `hydra-gate-route-reachability` (or the equivalent manual
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
- [x] R-2.2 Confirm `tests/unit/Controller/DossierControllerTest.php` still
  passes unchanged (it already calls `generateGrondslagenSummary()` directly
  and needs no edits).

### R-3. Verify the real click path (S)

- [x] R-3.1 Manually (or via Playwright) click "Append a grondslagen-summary
  page to each anonymised PDF (Wave 4a)" in `FolderAnonymizationView.vue` on a
  running dev instance and confirm the PDF regenerates instead of the request
  500ing.
