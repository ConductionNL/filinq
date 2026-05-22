## Tasks

- [ ] 1. Create database tables / register schemas for `redaction_job`, `redaction_profile`, `redaction_pattern`, `redaction_annotation`, `redacted_document`, `redaction_audit`. Decide on table-based vs. register-based storage (per ADR-001) and create migrations or register definitions.

- [ ] 2. Implement `RedactionPattern` library with six seed patterns (BSN/11-proef, IBAN/MOD-97, Telefoonnummer, Email, Postcode, Kenteken) in PHP or as seed data. Include regex definitions, category, validator implementations, and replacement text.

- [ ] 3. Implement pattern-matching engine (`PatternMatchingService`): compile and cache regexes, apply validators (11-proef for BSN, MOD-97 for IBAN), detect matches in document text, and create `RedactionAnnotation` records with status `pending`.

- [ ] 4. Implement NLP integration (`NLPEntityRecognitionService`): load a Dutch-language NER model (spaCy + NL core or equivalent), detect PERSON/ORG/LOC entities at configurable confidence threshold, apply allow-list suppression, create annotations with `originEntityType`. Make NLP optional and non-blocking (pattern matching proceeds even if NLP fails).

- [ ] 5. Implement `RedactionJob` orchestration service (`RedactionService`): handle job creation, status transitions (queued → running → completed/failed/partially_completed), dispatch to pattern matching and NLP phases, manage job persistence and error logging.

- [ ] 6. Implement `RedactionAnnotation` workflow: store annotation data (page, coordinates, sourceText encrypted at rest, category, status). Implement status transitions: pending → applied / rejected_by_reviewer. Log every status change in `RedactionAudit`.

- [ ] 7. Implement reviewer annotation editor UI (Vue component): display annotations with bounding rectangles, allow reviewer to add/remove/change category, attach notes. Make changes update UI instantly without re-running pattern matching. Integration: annotation editor calls backend API to persist changes.

- [ ] 8. Implement side-by-side preview UI: render original document left, proposed redacted right. Implement per-annotation toggle (click to apply/unapply annotation without re-matching). Load pages on-demand for large PDFs. Target < 2 seconds per page load.

- [ ] 9. Implement PDF text removal and export (`RedactionExportService`): mutate content stream to remove text objects, flatten annotations, re-extract text and verify zero matches against source fragments. Compute content hash (SHA-256). Fail export if verification does not pass.

- [ ] 10. Implement `RedactedDocument` storage: after successful export, create `RedactedDocument` record with sourceDocumentId, jobId, contentHash, accessibleTo roles, retainOriginalUntil timestamp. Store original document in encrypted archive (if applicable).

- [ ] 11. Implement unredact access (`UnredactService`): gate access by `unredact` role, check retention policy (retainOriginalUntil), log access in `RedactionAudit`, send notification to job owner. API endpoint: `GET /api/redactions/documents/<redactedDocumentId>/original`.

- [ ] 12. Implement bulk mode: accept folder/matter selection, queue per-document jobs, track progress (completed/running/failed/queued counts), implement pause/resume/cancel operations. Compute and return aggregate summary: total documents, per-category annotation counts, per-document status.

- [ ] 13. Implement `RedactionProfile` management: create, read, update, delete profiles. Fields: name, description, patterns[], entityTypes[], allowList[], denyList[], language, owner, sharedWith[], version. Enforce read-only sharing for non-owners. Increment version on update.

- [ ] 14. Implement profile versioning: version field (integer), profile-version history tracking, job-profile binding (job records profileId + profileVersion used). Make prior versions accessible for reproducibility.

- [ ] 15. Seed three default profiles: Woo-Publicatie, AVG-Inzageverzoek, Juridische Procedure. Seed six canonical patterns. Load seed data on app install/upgrade via existing loader mechanism.

- [ ] 16. Implement `RedactionAudit` logging: log every action (auto_detected, reviewer_added, reviewer_removed, applied, exported, original_accessed) with actor, timestamp, and context. Make audit records immutable (no delete, only append). Implement filtering by action/actor.

- [ ] 17. Implement REST API endpoints:
  - `POST /api/redactions/jobs` (create job)
  - `GET /api/redactions/jobs/<jobId>` (get job detail with status and statistics)
  - `GET /api/redactions/jobs/<jobId>/preview` (side-by-side preview)
  - `PATCH /api/redactions/jobs/<jobId>/annotations` (update annotation status/notes)
  - `POST /api/redactions/jobs/<jobId>/export` (export redacted document)
  - `GET /api/redactions/jobs/<jobId>/audit` (audit trail with filtering)
  - `POST /api/redactions/jobs/bulk` (start bulk job on folder/matter)
  - `GET /api/redactions/documents/<redactedDocumentId>/original` (unredact access)
  - `POST /api/redactions/profiles` (create profile)
  - `GET /api/redactions/profiles` (list profiles with filtering)
  - `PATCH /api/redactions/profiles/<profileId>` (update profile, increment version)
  - `GET /api/redactions/profiles/<profileId>/versions` (profile version history)

- [ ] 18. Implement async job processing: integrate with DocuDesk's existing job queue or background task system (or introduce a new queue if none exists). Ensure pattern matching/NLP runs async and jobs progress without blocking the API response.

- [ ] 19. Implement encryption for `RedactionAnnotation.sourceText` at rest using DocuDesk's existing encryption context or a new encryption service. Key should support selective decryption (only users with permission can decrypt).

- [ ] 20. Write unit tests for:
  - Pattern validators (11-proef for BSN, MOD-97 for IBAN) with valid/invalid examples
  - Regex matching and caching
  - Annotation status transitions
  - Profile versioning and sharing
  - Audit trail immutability and filtering
  - Encryption/decryption of sourceText

- [ ] 21. Write integration tests for:
  - End-to-end job lifecycle (queue → pattern match → review → export)
  - NLP entity recognition with allow/deny lists
  - Bulk job creation and per-document parallelism
  - Unredact access with role gating and audit logging
  - Export verification (re-extraction and zero-match assertion)

- [ ] 22. Add Dutch translations for:
  - Schema field titles and descriptions
  - Profile names and descriptions (six seed patterns, three seed profiles)
  - Annotation categories (BSN, IBAN, Telefoonnummer, Email, Postcode, Kenteken, PERSON, ORG, LOC)
  - Job status values (queued, running, completed, failed, partially_completed)
  - UI labels (Preview, Add Annotation, Remove, Approve, Reject, Export, Unredact)
  - Audit-trail action labels (auto_detected, reviewer_added, etc.)
  - Error messages (verification failed, retention expired, invalid profile, etc.)

- [ ] 23. Write feature documentation (`docs/features/redaction-at-scale.md`):
  - Overview and use cases (Woo, AVG, legal holds)
  - Pattern library and validators
  - Profile management and sharing
  - Reviewer workflow (annotation editor, preview, approval)
  - Bulk mode and progress tracking
  - Unredact access and audit trail
  - Export verification and retention policy
  - Legal and compliance notes (Woo Art. 5.1, AVG Art. 4(5), NIST SP 800-188, PDF 1.7)
  - Reference from top-level `docs/FEATURES.md` if present

- [ ] 24. Capture Playwright screenshots for feature documentation:
  - Redaction job list with sample jobs
  - Profile list with three seed profiles
  - Annotation editor (showing auto-detected annotations and reviewer additions)
  - Side-by-side preview with annotation toggle
  - Bulk job progress view with aggregate summary
  - Audit trail display
  - Unredact access confirmation dialog

- [ ] 25. Test pattern matching throughput: measure pages-per-minute on standard worker tier. Target ≥50 pages/minute. Profile and optimize regex caching and NLP integration if needed.

- [ ] 26. Test export verification: generate sample PDFs with redacted text, re-extract, and assert zero substring matches. Document re-extraction tools used and verification algorithm.

- [ ] 27. Test unredact role access control: verify only `unredact`-role users can retrieve originals, audit trail is logged correctly, job owner receives notification, retention policy is enforced.

- [ ] 28. Test bulk job with 100+ documents: verify parallelism, per-document failure isolation, aggregate summary accuracy, pause/resume/cancel operations.

- [ ] 29. Create or reference tracking issues in external systems (e.g., Linear) for downstream integration:
  - opencatalogi: Woo-besluit publication pipeline integration
  - docudesk e-discovery-legal-hold: privileged-passage redaction
  - openconnector: external NER service integration
  - mydash: redaction throughput dashboards
  - openregister: register-based profile/pattern storage (if applicable)

- [ ] 30. Document known limitations and future work:
  - Scanned PDFs (image-based) not supported in v1; OCR deferred to v2
  - Single-reviewer workflows only; multi-reviewer collaboration deferred to v2
  - No custom NLP model training per-tenant; off-the-shelf Dutch model only
  - No integration with Adobe or Word plugins
  - Annotation history and undo deferred
