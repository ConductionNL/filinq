## Tasks

### Schema & Register Setup

- [ ] 1. Add a `contract` register entry under `components.registers` in `lib/Settings/docudesk_register.json` with slug, Dutch title "Contracten Register", description, and schemas list: [contract, contractVersion, contractApproval, contractSignature, contractReminder, contractClause, contractClauseUsage]

- [ ] 2. Add `contract` schema under `components.schemas.contract` (required: contractNumber, title, counterpartyOrg, counterpartyContact, contractType, value, startDate, endDate, status; optional: description, noticePeriodDays, autoRenew, renewalTermMonths, owner, department, tags) with Dutch property titles, `objectNameField: "contractNumber"`, icon, and configuration for faceted search

- [ ] 3. Add `contractVersion` schema (required: contractId, versionNumber, createdBy, createdAt; optional: body, changeSummary, redlineFromVersion) with `immutable: true` for the body field, versionNumber auto-increment configuration

- [ ] 4. Add `contractApproval` schema (required: contractId, versionId, approverRole, approverUser, decision, sequenceOrder; optional: decidedAt, comment, delegatedTo) with decision enum values and configuration for sequential ordering

- [ ] 5. Add `contractSignature` schema (required: contractId, versionId, signatoryName, signatoryEmail, party, provider; optional: externalEnvelopeId, status, signedAt, certificateBlob) with provider enum, status enum, and `immutable: true` for signedAt

- [ ] 6. Add `contractReminder` schema (required: contractId, triggerType, triggerOffsetDays, recipients; optional: lastFiredAt, nextFireAt, escalation_recipients, escalation_days) with triggerType enum and array-type recipients

- [ ] 7. Add `contractClause` schema (required: clauseCode, title, category, body, jurisdiction, language; optional: deprecated, lastReviewedBy, lastReviewedAt) with category enum, jurisdiction enum, language enum, and `objectNameField: "title"`

- [ ] 8. Add `contractClauseUsage` schema (required: contractId, clauseCode, versionId, position) linking to contract + contractVersion + clause code

- [ ] 9. Validate JSON syntax: `jq . lib/Settings/docudesk_register.json > /dev/null` and confirm no validation errors

- [ ] 10. Confirm `composer check:strict` passes with the updated register

### Seed Data

- [ ] 11. Seed 6–10 `contractClause` objects (per design.md seed data section) covering categories: confidentiality, payment terms, limitation of liability, term and termination, IP ownership, governing law; include Dutch (NL/nl) and English (EN/en) versions

- [ ] 12. Seed 5 `contract` objects per personas (Gemeente Demostad 2, ReisBureau Zonnestraal 2, Conduction 1) with realistic statuses (active, expiring_soon, draft, in_review); counterparty organisations and contacts; and folder bindings (`@self.folder: seed-folder-<slug>`)

- [ ] 13. For seeded contracts, create initial `contractVersion` objects (version 1) with sample body content reflecting the contract type

- [ ] 14. For seeded "in_review" and "awaiting_signature" contracts, create `contractApproval` records demonstrating the approval chain (Legal → Finance for high-value contracts)

- [ ] 15. For seeded "active" contracts, create `contractReminder` objects with triggers (expiry 60 days, notice 90 days) and realistic recipient lists

- [ ] 16. For seeded active contracts, create one `contractSignature` record per contract demonstrating envelope status (e.g., signed for active contracts, pending for awaiting_signature)

### Service Layer

- [ ] 17. Create `ContractService` in `lib/Services/ContractService.php` with methods: `createContract()`, `updateContract()`, `getContract()`, `listContracts()`, `transitionStatus()` (with state-machine validation)

- [ ] 18. Implement state-machine validation in `ContractService::transitionStatus()` enforcing allowed transitions (draft → in_review → awaiting_signature → active → expiring_soon/expired; active/expiring_soon/expired → terminated; active → superseded via renewal)

- [ ] 19. Implement version creation in `ContractService::updateContract()`: when a contract in draft or in_review is edited, create a new `ContractVersion` preserving the prior version immutable

- [ ] 20. Implement redline diff generation: add `ContractService::getRedlineDiff($versionId1, $versionId2)` comparing two versions and producing insertions/deletions

- [ ] 21. Create `ContractApprovalService` with methods: `resolveApprovalChain()` (read approval policy, create ContractApproval records), `recordApproval()`, `canTransitionToSignature()` (check all approvals are granted)

- [ ] 22. Create `ContractSignatureService` with methods: `sendForSignature()` (call openconnector to create envelope), `handleWebhookCallback()` (update signature status), `transitionToActive()` (when all signatures collected), `downloadCertificate()`

- [ ] 23. Create `ContractReminderService` with methods: `evaluateReminders()` (called nightly), `createReminder()`, `updateReminder()`, `fireReminder()` (send notification + email)

- [ ] 24. Create `ContractRenewalService` with methods: `initiateRenewal()`, `validateRenewalEligibility()`, `createRenewalDraft()` (clone contract + version, link parent → child, set parent to superseded)

- [ ] 25. Create `ContractClauseService` with methods: `addClauseLibrary()`, `listClauses()`, `tagContractWithClause()`, `findContractsUsingClause()`, `deprecateClause()`, `importClausesFromJSON()`, `exportClausesToJSON()`

- [ ] 26. Create `ContractSearchService` with methods: `search()` (multi-faceted filtering, pagination), `export()` (CSV/XLSX), `applyAcl()` (respect read access per user)

### Controllers & API Endpoints

- [ ] 27. Create `ContractController` in `lib/Controllers/ContractController.php` with REST endpoints:
  - `POST /api/contracts` (create contract)
  - `GET /api/contracts` (list all)
  - `GET /api/contracts/<id>` (detail)
  - `PUT /api/contracts/<id>` (update)
  - `DELETE /api/contracts/<id>` (soft-delete or archive)

- [ ] 28. Add endpoints for versioning:
  - `GET /api/contracts/<id>/versions` (list versions)
  - `GET /api/contracts/<id>/versions/<versionId>` (detail + redline)
  - `GET /api/contracts/<id>/versions/<versionId>?include=redline` (redline diff)

- [ ] 29. Add endpoints for approvals:
  - `GET /api/contracts/<id>/approvals` (list approval chain)
  - `PUT /api/contracts/<id>/approvals/<approvalId>` (record decision: approve, reject, delegate)

- [ ] 30. Add endpoints for signatures:
  - `POST /api/contracts/<id>/send-for-signature` (initiate envelope)
  - `GET /api/contracts/<id>/signatures` (list signature status)
  - `GET /api/contracts/<id>/signature-certificate` (download certificate)

- [ ] 31. Add endpoints for reminders:
  - `GET /api/contracts/<id>/reminders` (list reminders)
  - `POST /api/contracts/<id>/reminders` (create custom reminder)
  - `DELETE /api/contracts/<id>/reminders/<reminderId>` (delete reminder)

- [ ] 32. Add endpoints for renewal:
  - `POST /api/contracts/<id>/renew` (initiate renewal workflow)
  - `GET /api/contracts/<id>/renewals` (list all renewals of a contract)

- [ ] 33. Add endpoints for clause library:
  - `GET /api/contract-clauses` (list clauses, with filters: category, jurisdiction, language)
  - `POST /api/contract-clauses` (add clause)
  - `GET /api/contract-clauses/<clauseCode>/usage` (find contracts using clause)
  - `POST /api/contracts/<id>/clause-usage` (tag contract with clause)
  - `GET /api/contract-clauses/export?format=json` (export library)
  - `POST /api/contract-clauses/import` (import from JSON)

- [ ] 34. Add search endpoints:
  - `GET /api/contracts/search` (multi-faceted search with pagination)
  - `GET /api/contracts/search/export?format=csv|xlsx` (export with ACL)

- [ ] 35. Add webhook endpoint for e-signature callbacks:
  - `POST /api/webhooks/signature-envelope/{provider}` (e.g., /docusign, /signhey)
  - Validate signature header using provider public key
  - Update ContractSignature status on valid callback

### Scheduled Tasks & Cron Jobs

- [ ] 36. Register background job `app:background:cron:contract:reminders` in `lib/BackgroundJobs/ContractRemindersJob.php`

- [ ] 37. Implement `ContractRemindersJob` to run nightly: load all reminders, evaluate triggers (nextFireAt ≤ today), fire notifications, update status, recalculate nextFireAt

- [ ] 38. Implement status transition job `app:background:cron:contract:status-updates` to nightly update contract statuses: active → expiring_soon (when endDate − noticePeriodDays ≤ today), active/expiring_soon → expired (when endDate ≤ today)

- [ ] 39. Register both jobs with the Nextcloud background job scheduler (or CronExpression for explicit time)

### Configuration & Admin Settings

- [ ] 40. Add configuration section `contract.approval_policies` (in `config.php` or admin settings) with default approval chains per contractType (e.g., SLA: Legal → Procurement if value > €25k)

- [ ] 41. Add configuration section `contract.signature_providers` with enabled providers and credentials (API key, secret, auth URL)

- [ ] 42. Add configuration section `contract.reminder_defaults` with default reminders (expiry 60 days, notice 90 days, review 30 days)

- [ ] 43. Add configuration section `contract.renewal_defaults` with default renewal term (months) if not specified per contract

- [ ] 44. Create admin settings UI for configuring the above (or use config.php + environment variables)

### Testing

- [ ] 45. Add PHPUnit tests `Tests/Unit/Services/ContractServiceTest.php` asserting:
  - Contract creation with required fields
  - State-machine transitions (allowed + rejected)
  - Version creation on edit
  - Redline diff generation

- [ ] 46. Add tests for `ContractApprovalServiceTest`: approval chain resolution, sequential enforcement, delegation, rejection

- [ ] 47. Add tests for `ContractSignatureServiceTest`: envelope creation, webhook callback validation, status transitions, certificate storage

- [ ] 48. Add tests for `ContractReminderServiceTest`: reminder evaluation, trigger calculation, notification firing, escalation

- [ ] 49. Add tests for `ContractRenewalServiceTest`: renewal eligibility, contract cloning, parent-child linking, status transitions

- [ ] 50. Add tests for `ContractClauseServiceTest`: clause library CRUD, usage tracking, deprecation warnings, import/export

- [ ] 51. Add tests for `ContractSearchServiceTest`: filtering, pagination, ACL enforcement, export (CSV/XLSX)

- [ ] 52. Run `phpunit -c phpunit-unit.xml --filter 'Contract.*' --coverage` and ensure ≥75% coverage on new code

- [ ] 53. Add Playwright integration tests for end-to-end workflows:
  - Create contract → Submit for review → Approve → Send for signature → Sign → Active
  - Expiry reminder → Renewal → Active new contract
  - Search by counterparty, status, clause, value

### Documentation

- [ ] 54. Write `docs/features/contract-lifecycle-management.md` describing:
  - Overview of CLM capabilities (lifecycle, versioning, approvals, signatures, reminders, renewal, clauses, search)
  - User workflows (create → approve → sign → manage → renew)
  - Administrator configuration (approval policies, signature providers, reminder defaults)
  - API reference (REST endpoints, request/response examples)
  - Integration with openconnector (e-signature providers)

- [ ] 55. Add CLM to `docs/FEATURES.md` with a link to the feature doc

- [ ] 56. Document the five seed contracts and three seed personas in the feature doc with example screenshots

- [ ] 57. Write configuration guide: how to set up approval policies, e-signature providers, and reminders (in `docs/admin/configuration.md` or similar)

### UI (Optional for v1, can be deferred to follow-up)

- [ ] 58. Implement Vue component `ContractList.vue` displaying contracts with status badges, expiry countdown, filtering, and sorting

- [ ] 59. Implement `ContractDetail.vue` showing contract metadata, versions, approvals, signatures, reminders, clause usage

- [ ] 60. Implement `ContractForm.vue` for creating/editing contracts with draft saving and validation

- [ ] 61. Implement `ApprovalWorkflow.vue` for approvers to review, approve, reject, or delegate approvals

- [ ] 62. Implement `SignatureEnvelope.vue` showing signature status, signatories, and manual upload option

- [ ] 63. Implement `ClauseLibrary.vue` for managing the clause library, importing/exporting, and tagging contracts

- [ ] 64. Implement `ContractSearch.vue` with multi-faceted filtering, saved searches, and export options

- [ ] 65. Implement dashboard widget `ContractSummary.vue` showing metrics (active count, expiring count, total value)

### Verification & Rollout

- [ ] 66. Install fresh and verify: reset env, enable DocuDesk, confirm `RegistersLoader` runs clean, register present via `occ openregister:registers:list`

- [ ] 67. Verify API: `GET /api/registers` includes contract register; `GET /api/contracts` lists seeded contracts; `GET /api/contract-clauses` lists seeded clauses

- [ ] 68. Verify seed data: 5 contracts + 6–10 clauses are present with expected statuses, counterparties, and reminders

- [ ] 69. Verify state machine: test all allowed transitions; verify rejected transitions return 400

- [ ] 70. Verify approval routing: submit a contract for review, confirm approvals created per policy, confirm approval workflow

- [ ] 71. Verify e-signature integration: mock DocuSign webhook callback, confirm status update and contract transition to active

- [ ] 72. Verify reminders: run nightly job manually, confirm notifications fired, lastFiredAt + nextFireAt updated

- [ ] 73. Verify renewal: initiate renewal, confirm new draft contract created with parent linkage, parent marked superseded

- [ ] 74. Verify search: perform multi-filter search, confirm results in < 2s, confirm export to CSV/XLSX

- [ ] 75. Run full test suite: `composer run test` and confirm ≥75% coverage, all tests passing

- [ ] 76. Verify translations: check Dutch + English labels in UI for schemas, properties, enums, and notifications

- [ ] 77. Perform security audit: validate webhook signatures, confirm ACL enforced on search/export, confirm sensitive fields (e.g., certificate blob) are not inadvertently logged

- [ ] 78. Document known limitations and open questions from design.md; open follow-up issues for v2 features (UI, advanced analytics, clause versioning depth, etc.)
