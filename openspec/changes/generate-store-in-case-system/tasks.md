# Tasks: generate-store-in-case-system

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 9. -->

## 1. Register

- [ ] 1.1 Add `caseSystemDestination` and `caseSystemDelivery` (with the `x-openregister-lifecycle`, relation and failure notification of design ADR-031 table) to `lib/Settings/filinq_register.json` in the register the bridge lands in; add `caseSystemDeliveryId` to `generatedDocument`; seed one inactive `hrmq` destination and one `written_back` delivery; bump the register version (REQ-GSC-001, REQ-GSC-003). Verify: `occ maintenance:repair` imports both schemas and the seeds show on the bridge panel.

## 2. Service and route

- [ ] 2.1 Add `lib/Service/CaseSystemDeliveryService.php`: pick the destination (template before namespace, request override), refuse a second active destination, write one delivery per document and destination (D2, D5) (REQ-GSC-001, REQ-GSC-002). Verify: PHPUnit on precedence, the duplicate refusal and idempotency.
- [ ] 2.2 Call it from `DocumentService::generateDocument()` and `generateBulkSync()` after a successful store; answer 400 for `caseSystem` with mode `return`; turn a delivery write failure into a warning (D3, D4) (REQ-GSC-002). Verify: PHPUnit on each mode and on a failing object write; Newman on the 400.
- [ ] 2.3 Add `POST api/bridge/deliveries/{id}/retry` with `#[AuthorizedAdminSetting]`, moving only `writeback_failed` to `ready_for_writeback` (REQ-GSC-003). Verify: Newman answers 409 for a delivery in any other state.

## 3. Screens

- [ ] 3.1 Add the destinations list and form, and the waiting and failed deliveries with retry, to the bridge status panel in `src/views/settings/` (REQ-GSC-001, REQ-GSC-003). Verify: Playwright `tests/e2e/case-system-delivery.spec.ts` adds a destination and retries a failed delivery.
- [ ] 3.2 Show the delivery state on the generated document's record where filinq lists produced documents (REQ-GSC-003). Verify: Playwright reads "In the case system" on the seeded delivered document.

## 4. Cross-app, strings, tests and docs

- [ ] 4.1 File the integriq issue for the push synchronisation on `caseSystemDelivery` and the humaniq issue for mode `both` (proposal, cross-app dependencies). Verify: both issue links are in the PR body.
- [ ] 4.2 Dutch and English strings (ADR-005) and `@spec` tags on every new method. Verify: `npm run lint`, `npm run check:l10n` and `composer check:strict` pass.
- [ ] 4.3 PHPUnit at 75% or more on the new service and route (ADR-009), and a section in `docs/features/` with a screenshot of the bridge panel with a delivery (ADR-010). Verify: the coverage report and the docs page.
