# Tasks: E-Discovery and Legal Hold

## Phase 1: Data Model & Registers

- [ ] 1. Add `matter` register entry under `components.registers` in `lib/Settings/docudesk_register.json` with slug, Dutch title "Zaken", description, and `schemas: ["matter"]`.
- [ ] 2. Add `matter` schema under `components.schemas.matter` with required `matterNumber`, `title`, `matterType` (enum: litigation, regulatory, woo, avg_inzage, internal_investigation); optional `description`, `dueDate`, `leadReviewer`, `jurisdiction`, `externalCaseReference`; required `status` (enum: open, on_hold, in_review, producing, closed); required `openedAt`, `openedBy`; optional `closedAt`, `closedBy`; Dutch titles, `objectNameField: "title"`, icon.
- [ ] 3. Add `legal-hold` register entry and schema with slug, Dutch title "Juridische Holds"; schema fields: required `matterId` (FK), `scopeDescription`; required `scopeFilter` (object: dateRange, custodians[], registers[], schemas[], keywords[], tags[]); required `issuedAt`, `issuedBy`, `status` (enum: active, released); optional `releasedAt`, `releasedBy`, `releaseReason`.
- [ ] 4. Add `custodian` register entry and schema with slug, Dutch title "Bewaarders"; schema fields: required `matterId` (FK), `user` (FK to Nextcloud user), `role` (enum: employee, manager, third_party, system); required `startedAt`; optional `endedAt`, `dataSources[]` (array of strings: email, file-share, database, mobile-device, etc.).
- [ ] 5. Add `hold-notice` register entry and schema with slug, Dutch title "Bewaarmeldingen"; schema fields: required `holdId` (FK), `custodianUser` (FK); optional `deliveredAt`, `acknowledgedAt`, `acknowledgementText`, `reminderCount` (default 0), `escalatedTo` (FK to manager user).
- [ ] 6. Add `review-tag` register entry and schema with slug, Dutch title "Beoordelingslabels"; schema fields: required `documentId` (FK), `matterId` (FK), `tag` (enum: responsive, not_responsive, privileged, hot, needs_redaction, confidential); required `taggedBy`, `taggedAt`; optional `notes`; ensure `matterId` + `documentId` + `tag` uniqueness constraint.
- [ ] 7. Add `production-set` register entry and schema with slug, Dutch title "Productiesets"; schema fields: required `matterId` (FK), `name`, `createdAt`, `createdBy`; `format` (enum: pdf_bundle, native_with_loadfile, encrypted_zip); `documentIds[]` (array of doc UUIDs); optional `passphrase` (hashed), `exportedAt`, `exportedBy`, `recipientName`, `recipientOrg`, `deliveryMethod`.
- [ ] 8. Add `access-audit` register entry and schema with slug, Dutch title "Toegangscontrole"; schema fields: required `matterId` (FK), `userId`, `action` (enum: viewed, downloaded, tagged, redacted, exported); optional `documentId` (FK), `occurredAt`, `ipAddress`, `userAgent`; mark schema as append-only (no UPDATE/DELETE); ensure immutability at schema level via OpenRegister settings if available, otherwise document current limitation.
- [ ] 9. Validate JSON syntax (`jq . lib/Settings/docudesk_register.json > /dev/null`) and verify `composer check:strict` passes.

## Phase 2: Seed Data

- [ ] 10. Seed four canonical matters from design.md (Gemeente Demostad Woo + Internal Investigation, Conduction B.V. AVG, ReisBureau Zonnestraal Complaint) under `components.objects` with realistic Dutch descriptions, `openedBy` set to corresponding user uuid, `@self` envelope for each.
- [ ] 11. Seed two holds (Woo subsidies, Internal investigation) with scopeFilters containing realistic dateRanges, custodians, registers, keywords; ensure `@self` envelope.
- [ ] 12. Seed six custodians (Finance Director + Culture Officer for Gemeente; HR + 4 Commission members for Gemeente; Privacy Officer for Conduction; Complaints Manager for Zonnestraal) with roles (employee, manager) and dataSources (email, file-share, etc.).
- [ ] 13. Seed four hold-notices (two acknowledged with reminders, two pending escalation) demonstrating full lifecycle: deliveredAt, acknowledgedAt with timestamp differences, reminderCount progression, escalatedTo.
- [ ] 14. Seed five review-tags across two matters and five documents, demonstrating all six tag types (responsive, not_responsive, privileged, hot, needs_redaction, confidential) with realistic notes (e.g., "Directe subsidie moet openbaargemaakt" for responsive).
- [ ] 15. Seed one production-set (Woo matter, 25 responsive documents, format=native_with_loadfile, status pre-export) and one completed production-set (with exportedAt + recipientName filled).
- [ ] 16. Seed three access-audit entries (viewed, tagged, downloaded) for the seeded documents across the seeded matters to demonstrate audit trail structure.
- [ ] 17. Install and verify: reset env, enable DocuDesk, run `RegistersLoader`, confirm registers are present via `occ openregister:registers:list`, and `GET /api/objects/matter` / `/legal-hold` / `/custodian` / `/review-tag` / `/production-set` / `/access-audit` return expected seed counts with complete fields.

## Phase 3: Retention Engine Integration

- [ ] 18. Modify `lib/Service/RetentionService.php` to call `HoldService::documentUnderActiveLold($documentId)` before deleting/archiving any document. If true, skip the action and log with hold reference.
- [ ] 19. Create `lib/Service/HoldService.php` with method `documentUnderActiveLold(string $documentId): bool` that queries active holds and evaluates scopeFilter against the document's properties (creation date, creator, register, tags). Cache active holds in-memory per request to reduce query count.
- [ ] 20. Add logging to retention skip path: log level INFO, message "Document {docId} skipped retention action due to active hold {holdId}".
- [ ] 21. Verify retention integration: create an active hold, place a document under it, run retention evaluation, assert document is not deleted and skip is logged.

## Phase 4: Custodian Hold-Notice Delivery

- [ ] 22. Create `lib/Service/CustodianNoticeService.php` with methods:
  - `deliverHoldNotice(Hold $hold, Custodian $custodian): void` — creates HoldNotice, delivers in-app notification + email
  - `sendReminder(HoldNotice $notice): void` — increments reminderCount, sends reminder email
  - `escalateToManager(HoldNotice $notice): void` — sets escalatedTo = manager user ID, sends escalation notification
- [ ] 23. Implement hold notice delivery triggers: when hold status changes to "active", iterate custodians and call `deliverHoldNotice()` for each.
- [ ] 24. Implement reminder dispatch: create a background job (cron/queue) that runs daily, finds HoldNotices with `acknowledgedAt: null` and `createdAt > threshold`, calls `sendReminder()` and increments reminder count.
- [ ] 25. Implement escalation logic: when `reminderCount` exceeds configured threshold (default 2), call `escalateToManager()` instead of sending another reminder.
- [ ] 26. Create email template `resources/views/emails/hold_notice.html.twig` with hold title, description, custodian name, due date, acknowledgement link.
- [ ] 27. Create email template `resources/views/emails/hold_reminder.html.twig` with escalation message if reminders exceeded threshold.
- [ ] 28. Create API endpoint `PUT /api/objects/hold-notice/<uuid>/acknowledge` that accepts optional `acknowledgementText`, sets `acknowledgedAt = now()`, logs to AccessAudit with action "acknowledge", records in audit trail.
- [ ] 29. Test custodian notice workflow: activate a hold with 2 custodians, verify both receive notice, acknowledge as one custodian, verify second receives reminders at configured intervals.

## Phase 5: Document Review & Tagging

- [ ] 30. Create `lib/Service/ReviewService.php` with methods:
  - `queryDocumentsInMatter(Matter $matter, array $filters): array` — full-text search + metadata filtering (dateRange, tags, responsive status)
  - `applyTag(Document $doc, Matter $matter, string $tag, string $notes = null): ReviewTag` — creates ReviewTag, logs to AccessAudit
  - `findTags(Document $doc, Matter $matter): array[ReviewTag]` — returns all tags for doc in context of matter
- [ ] 31. Implement search performance: use `IndexService` or `SolrController` (platform search) with matter scope filter to achieve <3 sec response for 100k documents.
- [ ] 32. Create Vue component `src/components/ReviewSurface.vue` with:
  - Search bar (keyword + date range + tag filters)
  - Document list with pagination
  - Document detail panel with tagging UI (checkboxes for responsive/not_responsive/privileged/hot/needs_redaction/confidential)
  - Notes field (rich text)
  - Audit trail tab showing who tagged what when
- [ ] 33. Create API endpoint `POST /api/objects/review-tag` to apply tags, enforce POST-only (no PUT/DELETE for immutability).
- [ ] 34. Ensure ReviewTag audit trail: every tag creation logs to AccessAudit with `action: "tagged"`, `userId`, `documentId`, `matterId`, `occurredAt`.
- [ ] 35. Verify review workflow: create matter + 10 seed documents, search by keyword, apply tags to 3 docs, verify audit trail records each action.

## Phase 6: Privilege Redaction Gate

- [ ] 36. Modify `lib/Service/ProductionExportService.php` to check: before exporting, iterate documentIds in ProductionSet, find all ReviewTags with `tag: "privileged"` or `tag: "needs_redaction"`, and verify each has a `redactionApprovedAt` timestamp in the document's metadata (or a field we add to Document schema).
- [ ] 37. If any privileged/needs_redaction document is missing redaction approval, throw `ProductionExportException` with error message listing all unredacted documents.
- [ ] 38. Create API response for blocked export that includes:
  - HTTP 400 Bad Request
  - Error message: "Cannot export production set: N documents require redaction approval before inclusion"
  - List of unredacted document IDs + titles
  - Suggested next steps (e.g., "Contact redaction team to approve these documents")
- [ ] 39. Test redaction gate: create production set with 1 responsive + 1 privileged unredacted doc, attempt export, verify 400 error + list.
- [ ] 40. Test redaction approval: set redactionApprovedAt on privileged doc, retry export, verify success.

## Phase 7: Production-Set Export

- [ ] 41. Create `lib/Service/ProductionExportService.php` with method `exportProductionSet(ProductionSet $set, string $format, string $passphrase): string` (returns path to generated ZIP).
- [ ] 42. Implement ZIP generation: use `ZipArchive` PHP extension to create encrypted ZIP, add documents in native format (PDF, Office, images).
- [ ] 43. Generate load file (CSV format) with columns: [bates_number, original_path, md5_hash, document_uuid, responsiveness_tag, file_size, date_created].
  - Bates numbering: sequential, e.g., "MATTER-00001", "MATTER-00002", … (stored on ProductionSet or dynamically computed)
  - MD5 hash: computed from document file content
  - original_path: e.g., "/path/to/Folder/Subfolder/Document.pdf"
  - responsiveness_tag: value from ReviewTag (responsive, not_responsive, privileged, etc.)
- [ ] 44. Encrypt ZIP with AES-256 passphrase using `php-zip` or `openssl` command-line.
- [ ] 45. Store ZIP file in a production archive (FileService in OpenRegister or file system with path in ProductionSet object).
- [ ] 46. Set `exportedAt = now()`, `exportedBy = current user`, on ProductionSet and log to AccessAudit with `action: "exported"`.
- [ ] 47. Create API endpoint `POST /api/objects/production-set/<uuid>/export` that:
  - Validates ProductionSet belongs to a matter the user can access (legal_hold_admin or delegated reviewer)
  - Calls privilege gate (step 36–40)
  - Generates encrypted ZIP + load file
  - Returns download link + passphrase delivery instructions (e.g., "Passphrase will be sent via secure email")
- [ ] 48. Test production export: create production set, verify ZIP is encrypted, load file is correct, download and verify contents.
- [ ] 49. Passphrase delivery: generate random 20-char passphrase, hash for storage, **document that passphrase delivery is currently manual** (email, SMS, phone call) — v1 does not implement automated delivery. Add TODO comment.

## Phase 8: Immutable Access Audit

- [ ] 50. Ensure `access-audit` schema in `docudesk_register.json` is marked read-only (no UPDATE/DELETE). Document in schema description that OpenRegister's schema-level immutability enforcement (`immutable: "append-only"`) is a follow-up; current approach relies on archival status or API-level enforcement.
- [ ] 51. Create middleware/service to intercept UpdateObject, DeleteObject calls on `access-audit` register and return 405 Method Not Allowed.
- [ ] 52. Add logging points for AccessAudit creation:
  - Document view: log in DocumentController when document detail is fetched (`action: "viewed"`)
  - Document download: log in FileService when document is downloaded (`action: "downloaded"`)
  - Tag application: log in ReviewService when tag is created (`action: "tagged"`)
  - Redaction approval: log when redactionApprovedAt is set (`action: "redacted"`)
  - Export: log in ProductionExportService when ZIP is generated (`action: "exported"`, matterId only, no documentId)
- [ ] 53. Each AccessAudit entry captures: `matterId`, `documentId` (if applicable), `userId`, `action`, `occurredAt`, `ipAddress` (from request), `userAgent` (from request header).
- [ ] 54. Create API endpoint `GET /api/objects/matter/<uuid>/audit-report` that:
  - Fetches all AccessAudit entries for the matter
  - Returns as CSV or JSON with summary stats (total actions, unique users, date range, action breakdown)
  - Logs the export action itself to AccessAudit
- [ ] 55. Test audit immutability: create audit entries, attempt UPDATE/DELETE via API, verify 405/403 error.
- [ ] 56. Test audit report: run queries on matter, tag documents, download docs, generate audit report, verify all actions are captured.

## Phase 9: Hold Release & Notification

- [ ] 57. Create endpoint `PUT /api/objects/legal-hold/<uuid>/release` that:
  - Sets `status: "released"`, `releasedAt: now()`, `releasedBy: current user`, `releaseReason: <provided reason>`
  - Logs to audit trail with actor + reason
  - Calls `CustodianNoticeService::notifyHoldReleased(hold)` to send release notifications
- [ ] 58. Implement `notifyHoldReleased()` method that iterates all HoldNotices for the hold and sends release notification (in-app + email) to each custodian.
- [ ] 59. Create email template `resources/views/emails/hold_released.html.twig` with matter title, release reason, and message that documents are no longer under preservation.
- [ ] 60. Test hold release: activate hold, notify custodians, release hold, verify release notifications are sent, verify retention evaluation resumes.

## Phase 10: API Controllers & Authorization

- [ ] 61. Create `lib/OCS/MatterController.php` with endpoints:
  - `GET /api/objects/matter` — list matters (all users can view; filter by leadReviewer or all if admin)
  - `POST /api/objects/matter` — create matter (legal_hold_admin only)
  - `GET /api/objects/matter/<uuid>` — detail (authorized users only)
  - `PUT /api/objects/matter/<uuid>` — update status, dueDate, etc. (legal_hold_admin only)
- [ ] 62. Create `lib/OCS/HoldController.php` with endpoints:
  - `POST /api/objects/legal-hold` — create hold (legal_hold_admin only)
  - `GET /api/objects/legal-hold?matterId=<uuid>` — list holds for matter
  - `PUT /api/objects/legal-hold/<uuid>/release` — release hold (legal_hold_admin only)
- [ ] 63. Create `lib/OCS/CustodianController.php` with endpoints:
  - `POST /api/objects/custodian` — add custodian to matter (legal_hold_admin only)
  - `GET /api/objects/custodian?matterId=<uuid>` — list custodians for matter
- [ ] 64. Create `lib/OCS/ReviewController.php` with endpoints:
  - `GET /api/objects/matter/<uuid>/search?q=<keyword>&dateRange=...` — search documents (any authenticated user can search matters they can access)
  - `POST /api/objects/review-tag` — apply tag (reviewers only; must validate documentId + matterId scoping)
  - `GET /api/objects/review-tag?documentId=<uuid>&matterId=<uuid>` — list tags for document in matter
- [ ] 65. Create `lib/OCS/ProductionController.php` with endpoints:
  - `POST /api/objects/production-set` — create production set (legal_hold_admin only)
  - `POST /api/objects/production-set/<uuid>/export` — trigger export (legal_hold_admin only)
  - `GET /api/objects/production-set/<uuid>/load-file` — fetch load file CSV/JSON
- [ ] 66. Create `lib/OCS/AuditController.php` with endpoints:
  - `GET /api/objects/matter/<uuid>/audit-report` — audit report (legal_hold_admin + matter owner only)
  - `GET /api/objects/matter/<uuid>/audit-export?format=csv` — export audit as CSV
- [ ] 67. Enforce authorization: every endpoint must check that the authenticated user is `legal_hold_admin` OR has explicit access to the matter (leadReviewer, etc.). Use `AuthorizationService` from ADR-005.
- [ ] 68. Test authorization: attempt to access/modify a matter as non-admin non-leadReviewer user, verify 403 Forbidden.

## Phase 11: Vue Components & UI

- [ ] 69. Create `src/views/MatterListView.vue` with:
  - Table of matters (matterNumber, title, matterType, dueDate, status, leadReviewer)
  - Filters (matterType, status, dueDate range)
  - "New Matter" button
  - Link to each matter detail
- [ ] 70. Create `src/views/MatterDetailView.vue` with:
  - Matter metadata (read-only or editable for admin): matterNumber, title, type, status, dueDate, leadReviewer, description
  - Tabs: Holds, Custodians, Documents (review surface), Production Sets, Audit Trail
  - Inline actions (activate hold, create production set, release hold, etc.)
- [ ] 71. Create `src/views/HoldDetailView.vue` with:
  - Hold metadata: scope description, status, issued by/date, released by/date/reason
  - Scope filter summary (custodians, date range, registers, keywords)
  - List of HoldNotices (custodians + acknowledgement status)
  - Inline action: release hold, resend notices, send reminders
- [ ] 72. Create `src/components/ReviewSurface.vue` (step 32) with search + tagging UI.
- [ ] 73. Create `src/components/ProductionSetForm.vue` with:
  - Select format (encrypted_zip, native_with_loadfile, pdf_bundle)
  - Select documents (from review-tagged responsive set, checkboxes)
  - Preview: count responsive + unredacted privileged docs
  - Export button (calls ProductionExportService)
- [ ] 74. Create `src/components/HoldNoticePanel.vue` with:
  - List of HoldNotices for the current hold
  - Status badges (pending, acknowledged, escalated)
  - "Acknowledge" button for custodian (if viewing as custodian)
  - Reminder/escalation history (read-only)
- [ ] 75. Create `src/components/AccessAuditReport.vue` with:
  - Summary stats (total actions, unique users, date range, breakdown by action type)
  - Table of audit entries (filterable by action, user, date)
  - "Export as CSV" button
- [ ] 76. Add navigation: sidebar link "E-Discovery" → matter list.

## Phase 12: Testing & Verification

- [ ] 77. Add PHPUnit tests `Tests/Unit/Service/HoldServiceTest.php`:
  - Test `documentUnderActiveLold()` with various scope filters (date range, custodians, registers)
  - Test caching behavior (active holds cache)
  - Verify performance target (sub-millisecond for cached queries)
- [ ] 78. Add PHPUnit tests `Tests/Unit/Service/RetentionHoldIntegrationTest.php`:
  - Test that retention engine skips documents under active hold
  - Test that retention resumes after hold is released
  - Test logging of hold-skip action
- [ ] 79. Add PHPUnit tests `Tests/Unit/Service/CustodianNoticeServiceTest.php`:
  - Test `deliverHoldNotice()` creates HoldNotice and sends email/in-app notification
  - Test `sendReminder()` increments counter
  - Test `escalateToManager()` updates escalatedTo field
- [ ] 80. Add PHPUnit tests `Tests/Unit/Service/ReviewServiceTest.php`:
  - Test `queryDocumentsInMatter()` with filters
  - Test `applyTag()` creates ReviewTag and logs AccessAudit
  - Test uniqueness constraint (can't apply same tag twice to same doc in same matter)
- [ ] 81. Add PHPUnit tests `Tests/Unit/Service/ProductionExportServiceTest.php`:
  - Test privilege redaction gate blocks unredacted privileged docs
  - Test ZIP generation with load file
  - Test encryption with passphrase
  - Test that export is logged to AccessAudit
- [ ] 82. Add PHPUnit tests `Tests/Unit/AccessAuditTest.php`:
  - Test that UpdateObject + DeleteObject on access-audit register are rejected (405/403)
  - Test that read operations (GET) succeed
  - Test that audit entries are created for document interactions
- [ ] 83. Add browser-based tests (Playwright/Cypress):
  - Create matter, activate hold, verify custodian receives notice
  - Search documents in review surface, apply tags, verify tags appear in list
  - Create production set, verify privileged-doc gate blocks export, approve redaction, export succeeds
  - View audit report, verify all actions are logged
- [ ] 84. Verify seed data loads correctly: reset env, enable DocuDesk, run `RegistersLoader`, check via `GET /api/objects/matter?_limit=100` that all four seed matters are present with expected fields.
- [ ] 85. Verify performance targets:
  - Search 100k-document matter by keyword: assert <3 seconds
  - Query active holds for document: assert <100ms (with caching)
  - Generate 10k-document production ZIP: measure time and size
- [ ] 86. Verify standards compliance:
  - Document that EDRM phases (identify/preserve → collect → process → review → produce → present) are supported by the system
  - Document FRCP Rule 37(e) spoliation defensibility via hold scope definition + audit trail
  - Document AVG Art. 15 + Woo compliance (matter type classification, deadline tracking, data export)

## Phase 13: Documentation & Finalization

- [ ] 87. Write `docs/features/e-discovery-legal-hold.md` with:
  - Overview of legal hold, EDRM phases, use cases (litigation, Woo, AVG, internal investigation)
  - Matter creation workflow (matter types, status, lead reviewer, due date)
  - Hold scope definition (custodians, date range, registers, keywords; scope evaluation)
  - Custodian hold-notice workflow (delivery, acknowledgement, reminders, escalation)
  - Document review + tagging (search, tag types, audit trail)
  - Production-set export (format options, privilege redaction, load file, passphrase)
  - Access audit (immutability, export, compliance)
  - Configuration (reminder days, escalation threshold, retention integration)
- [ ] 88. Add links from main docs/FEATURES.md to e-discovery-legal-hold.md
- [ ] 89. Create API documentation (`docs/api/e-discovery.md`) with endpoint examples, auth, response schemas.
- [ ] 90. Add configuration defaults to `lib/Settings/docudesk_config.json`:
  - `hold.reminder.days: 3` (send reminder 3 days after delivery if not acknowledged)
  - `hold.escalation.threshold: 2` (escalate to manager after 2 reminders)
  - `production.retention.years: 7` (keep exported production sets for 7 years)
  - `audit.retention.years: 10` (keep audit trail for 10 years per statute of limitations + retention)
- [ ] 91. Take Playwright MCP screenshots for docs:
  - Matter list with four seed matters
  - Matter detail view with holds tab expanded
  - Hold detail with custodian notices (acknowledged + escalated)
  - Review surface with search results + tags
  - Production set export preview
  - Audit report summary
- [ ] 92. Update top-level README to mention e-discovery capability for Dutch legal/government users.
- [ ] 93. Run `phpunit -c phpunit-unit.xml` for all new tests; target ≥75% code coverage on new services.
- [ ] 94. Run `phpunit -c phpunit-integration.xml` for integration tests (retention hook, custodian notice, review workflow, export).
- [ ] 95. Run `psalm` (static analysis) and fix any errors.
- [ ] 96. Run Playwright tests end-to-end; capture screenshots for docs.
- [ ] 97. Create a tracking issue for follow-up work:
  - OpenRegister `immutable: "append-only"` schema flag (for hard AccessAudit enforcement)
  - Third-party custodian email-token flow (for external custodian notice delivery without Nextcloud login)
  - Advanced redaction UI (mark-up, OCR-based PII detection)
  - E-discovery platform sync (Relativity, Everlaw)
  - Automated passphrase delivery (encrypted email, SMS)
  - Holds with auto-updating scope (e.g., "all emails from custodian after date X" that auto-includes new emails)

---

## Deduplication Check

**Existing OpenRegister Services & Components Leveraged:**
- `ObjectService::saveObject()`, `deleteObject()`, `findAll()` — matter/hold/custodian CRUD
- `AuditTrailService::findByObject()` — hold-notice acknowledgement history, review-tag history
- `NotificationService::sendNotification()` — hold-notice + reminder + escalation + release notifications
- `FileService::uploadFile()`, `downloadFile()` — production-set ZIP storage and retrieval
- `IndexService` / `SolrController` — search and review queries (full-text + filters)
- `AuthorizationService` — per-matter access control
- `ArchivalService` — integration with retention engine

**No Overlap Found:** The e-discovery workflow (legal hold, custodian notice, production export, review tagging, immutable audit) is domain-specific to DocuDesk and not covered by existing specs (anonymization, document register, metadata enrichment, etc.). OpenRegister provides the plumbing; DocuDesk builds the legal workflow on top.

## Notes

- **Retention integration risk**: The hold-suspension hook adds query overhead to every retention evaluation. Mitigation: cache active holds in-memory per request and per app-session with invalidation TTL (e.g., 1 hour).
- **AccessAudit immutability gap**: OpenRegister does not currently enforce schema-level append-only semantics at the API level. Current v1 approach relies on middleware + archival status. Follow-up change `append-only-schemas` (OpenRegister) will add schema flag.
- **Passphrase delivery**: v1 does not implement automated secure delivery (encrypted email, SMS). Passphrase is generated, hashed for storage, and **manual delivery is documented**. Follow-up change can add encrypted email + SMS options.
- **Performance baseline**: Before merge, benchmark production-set export time for 10k, 50k, 100k documents; target <5 min for 100k. If slower, add async job queue support.
