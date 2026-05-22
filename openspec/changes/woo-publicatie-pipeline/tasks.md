# Tasks: WOO Publicatie Pipeline

## Phase 1: Data Models & OpenRegister Integration

- [ ] Design and implement `WooCategory` schema (all 17 categories with legal references)
  - [ ] Create OpenRegister entity definition with fields: code, wettelijkeGrondslag, titleNl, descriptionNl, publishWithinDays, publicationFrequency, checklistItems, koopMetadataMapping
  - [ ] Seed all 17 categories from WOO art. 3.3
  - [ ] Add database migrations for WooCategory table
  - [ ] Create API endpoints: GET /api/woo/categories, GET /api/woo/categories/{id}

- [ ] Design and implement `WooPublication` schema
  - [ ] Create OpenRegister entity definition with fields: documentId, documentVersion, wooCategory, title, publicationDate, publishedAt, publicationStatus, publisherOrganisation, publicationOfficer, koopReference, publishedUrl, retentionLinkedTo, exemptionsApplied, summary, languageTag
  - [ ] Add database migrations for WooPublication table
  - [ ] Create status enum: draft | queued | submitted | accepted | live | rejected | withdrawn | bezwaar-pending
  - [ ] Create API endpoints: POST /api/woo/publications, GET /api/woo/publications/{id}, PATCH /api/woo/publications/{id}/status
  - [ ] Implement immutable audit log for status transitions (via OpenRegister)

- [ ] Design and implement `WooAnonymisationCheck` schema
  - [ ] Create OpenRegister entity definition with fields: publicationId, runAt, runBy, ruleSetVersion, findings[], reviewedBy, reviewedAt, approvedRedactionPdfRef, hashBefore, hashAfter
  - [ ] Each finding: ruleId, locationRef, snippet, severity, action
  - [ ] Add database migrations
  - [ ] Create API endpoints: POST /api/woo/anonymisation-checks, GET /api/woo/anonymisation-checks/{id}

- [ ] Design and implement `WooExemption` schema
  - [ ] Create OpenRegister entity definition with fields: publicationId, exemptionArticle, exemptionScope, justification, weighingTest, decisionBy, decisionDate, expiresAt
  - [ ] Add database migrations
  - [ ] Create API endpoints: POST /api/woo/exemptions, GET /api/woo/exemptions/{id}

- [ ] Design and implement `WooBezwaar` schema
  - [ ] Create OpenRegister entity definition with fields: publicationId, bezwaarmaker, bezwaarType, submittedAt, deadlineAt, assignedTo, status, decisionAt, decisionDocument, beroepCaseRef
  - [ ] Add database migrations
  - [ ] Create status enum: received | in-review | gegrond | ongegrond | ingetrokken | beroep-pending
  - [ ] Create API endpoints: POST /api/woo/bezwaren, GET /api/woo/bezwaren/{id}, PATCH /api/woo/bezwaren/{id}/decision

---

## Phase 2: Category Suggestion & Confirmation

- [ ] Implement category suggestion engine (REQ-WPP-01)
  - [ ] Integrate zaaktype-to-category mapping
  - [ ] Implement keyword/regex-based content classifier (fallback to catch-all category)
  - [ ] Return suggestion with confidence score (0.0–1.0)
  - [ ] API endpoint: POST /api/woo/publications/suggest-category with document metadata

- [ ] Implement category confirmation workflow (REQ-WPP-02)
  - [ ] UI: Display suggested category with option to confirm or override
  - [ ] On override: require justification (min 10 characters)
  - [ ] Audit log entry: record decision (confirm vs. override + justification)
  - [ ] API endpoint: POST /api/woo/publications/{id}/confirm-category

---

## Phase 3: Anonymisation Integration

- [ ] Integrate docudesk/anonymization pipeline
  - [ ] Reuse existing detection service for PII/bedrijfsgevoelig/beveiligingsrubricering
  - [ ] Configure WOO-specific rule sets per organisation type (gemeenten / Rijk / waterschappen)
  - [ ] Call detection service: POST /api/anonymization/detect with document + rule set
  - [ ] Parse findings and create WooAnonymisationCheck record

- [ ] Implement structured findings storage (REQ-WPP-05)
  - [ ] Extract location info from anonymization service (page, line, pixel coordinates)
  - [ ] Store in WooAnonymisationCheck.findings[] with locationRef format: `page:N,line:M` or `page:N,x:X,y:Y`
  - [ ] Implement finding sorting (by page/location)
  - [ ] API endpoint: GET /api/woo/anonymisation-checks/{id}/findings

- [ ] Implement redaction with visible boxes + text-layer scrubbing (REQ-WPP-06, REQ-WPP-07)
  - [ ] Integrate docudesk/anonymization redaction service
  - [ ] Apply black box redaction (PDF painting) + text-layer scrubbing
  - [ ] Strip PDF metadata, thumbnails, form fields, comments
  - [ ] Output: redacted PDF file stored in file system
  - [ ] API endpoint: POST /api/woo/anonymisation-checks/{id}/apply-redaction

- [ ] Implement hash capture for tamper evidence (REQ-WPP-08)
  - [ ] Compute SHA-256 hash of original document
  - [ ] Store as WooAnonymisationCheck.hashBefore
  - [ ] After redaction, compute SHA-256 hash of redacted output
  - [ ] Store as WooAnonymisationCheck.hashAfter
  - [ ] Audit log: record both hashes with timestamps

---

## Phase 4: Reviewer Approval Gate

- [ ] Integrate docudesk/anonymization-entity-review
  - [ ] Queue redacted document for reviewer approval
  - [ ] Reviewer can approve, reject, or request changes
  - [ ] On approval: set WooAnonymisationCheck.reviewedBy, .reviewedAt, .status = "approved"
  - [ ] Status transition gate: publication cannot move from draft → queued without approval
  - [ ] API endpoint: PATCH /api/woo/anonymisation-checks/{id}/approve-or-reject

- [ ] Implement approval gating (REQ-WPP-09)
  - [ ] Validate WooAnonymisationCheck.status = "approved" before allowing PLOOI submission
  - [ ] Error message: "Redaction must be reviewer-approved before submission"
  - [ ] Implement approval timeout alerting (5 business days)

---

## Phase 5: PDF/A-2 + DIWOO-XML Generation

- [ ] Implement PDF/A-2 output generation (REQ-WPP-10)
  - [ ] Integrate mPDF or similar library for PDF/A-2 encoding
  - [ ] Add XMP metadata specifying PDF/A-2 profile (ISO 19005-2)
  - [ ] Validate output with veraPDF or KOOP validation tool
  - [ ] API endpoint: POST /api/woo/publications/{id}/generate-pdf-a2

- [ ] Implement DIWOO-XML metadata generation (REQ-WPP-10, REQ-WPP-24)
  - [ ] Create XML template per WooCategory (raadsstukken, jaarplannen, etc.)
  - [ ] Populate mandatory fields per category requirements:
    - Raadsstukken: dct:title, dcterms:issued, dcat:eventDate, dcat:agendaItem
    - Jaarplannen: dct:title, dcterms:issued, dcat:planCoverage
    - ...etc.
  - [ ] Implement metadata validation against KOOP DIWOO schema
  - [ ] Return validation errors with field-specific guidance
  - [ ] API endpoint: POST /api/woo/publications/{id}/generate-diwoo-xml

- [ ] Implement metadata auto-fill (REQ-WPP-24)
  - [ ] Extract document metadata (title, date, zaaktype) and auto-populate DIWOO fields
  - [ ] Allow coordinator to manually correct/add fields
  - [ ] Validate completeness before submission

---

## Phase 6: PLOOI Submission via OpenConnector

- [ ] Integrate OpenConnector for PLOOI Aanleverkanaal API (REQ-WPP-11, REQ-WPP-12)
  - [ ] Configure mTLS certificate (organisation credentials)
  - [ ] Implement submission payload: PDF/A-2 + DIWOO-XML
  - [ ] API call: POST to KOOP Aanleverkanaal endpoint with multipart file upload
  - [ ] Handle large files (150+ MB) with chunked/streaming upload
  - [ ] Capture koopReference from response
  - [ ] Transition status: queued → submitted

- [ ] Implement retry logic with exponential backoff (REQ-WPP-12)
  - [ ] On network timeout/error: retry after 5, 10, 20, 40 seconds (max 5 retries)
  - [ ] On HTTP 503 (PLOOI temporary outage): treat as retriable, apply backoff
  - [ ] On HTTP 400 (validation error): log and escalate (do not retry)
  - [ ] After final failure: status transitions to "rejected", coordinator notified
  - [ ] Implement manual retry mechanism after coordinator fixes issue

- [ ] Implement async status polling (REQ-WPP-13)
  - [ ] Poll PLOOI for submission status after submission
  - [ ] On acceptance: koopReference confirmed, publishedUrl set, status → "accepted"
  - [ ] On rejection: parse rejection reason, status → "rejected"
  - [ ] Audit log: record all poll attempts and responses
  - [ ] Background job: poll every 5 minutes for pending submissions

---

## Phase 7: Status Tracking & Audit Log

- [ ] Implement per-publication status transitions (REQ-WPP-13)
  - [ ] State machine: draft → queued → submitted → accepted → live
  - [ ] Alt path: queued → rejected (PLOOI rejection or final-failure escalation)
  - [ ] Alt path: live → withdrawn (bezwaar decision or coordinator action)
  - [ ] Alt path: live → bezwaar-pending (bezwaar received)

- [ ] Implement immutable audit log (REQ-WPP-13)
  - [ ] On each status transition, create audit entry with:
    - timestamp, fromStatus, toStatus, triggeredBy, reason
  - [ ] Store via OpenRegister (immutable)
  - [ ] API endpoint: GET /api/woo/publications/{id}/audit-log

- [ ] Implement KOOP rejection reason parsing (REQ-WPP-14)
  - [ ] Parse PLOOI rejection response JSON for structured error info
  - [ ] Extract: field name, reason, suggested action
  - [ ] Display to coordinator with fix suggestions
  - [ ] Fallback: show raw response if parse fails
  - [ ] Escalation: email alert to coordinator

---

## Phase 8: Exemption Workflow

- [ ] Implement exemption (uitzonderingsgrond) workflow (REQ-WPP-22, REQ-WPP-23)
  - [ ] UI: Create/edit WooExemption with fields:
    - exemptionArticle (WOO art. 5.1.x / 5.2.x dropdown)
    - exemptionScope (full | partial-page | partial-paragraph)
    - justification (text field)
    - weighingTest (belangenafweging text field)
    - decisionBy (user picker)
    - decisionDate (date picker)
    - expiresAt (optional, for temporary exemptions)
  - [ ] Validation: require all mandatory fields
  - [ ] Approval gate: exemption must be signed by manager/bestuurder (digital signature)
  - [ ] API endpoint: POST /api/woo/exemptions, PATCH /api/woo/exemptions/{id}

- [ ] Implement partial publication support (REQ-WPP-23)
  - [ ] On partial exemption: exclude exempted pages/paragraphs from PDF/A-2 generation
  - [ ] DIWOO-XML: add note "Pages withheld per WOO art. 5.1.x"
  - [ ] UI feedback: "Published (4 of 5 pages; 1 page withheld per exemption)"
  - [ ] Partial exemption → different handling than full-document redaction

- [ ] Implement full-document exemption blocking (REQ-WPP-22)
  - [ ] If exemptionScope = "full": block publication entirely
  - [ ] Status: "exempt" (not submitted to PLOOI)
  - [ ] Record for IOBJ annual report

- [ ] Implement exemption expiry alerting (REQ-WPP-22)
  - [ ] If exemption has expiresAt date
  - [ ] When expiry is 30 days away: alert coordinator
  - [ ] When expired: status changes to "review-for-publication" (exemption no longer valid)

---

## Phase 9: Bezwaar Workflow

- [ ] Implement bezwaar intake (REQ-WPP-15)
  - [ ] UI: Record incoming bezwaar with fields:
    - bezwaarmaker (party name)
    - bezwaarType (publication-should-not-have-happened | wrong-redaction | wrong-category | personal-data-exposure)
    - submittedAt (date/time)
  - [ ] Auto-calculate deadlineAt = submittedAt + 42 days (per Awb 7:1)
  - [ ] Assign to legal team member
  - [ ] Status: "received"
  - [ ] API endpoint: POST /api/woo/bezwaren

- [ ] Implement bezwaar deadline tracking and alerting (REQ-WPP-15)
  - [ ] Daily job: scan for bezwaar with approaching deadlines
  - [ ] Alert at: 30 days, 14 days, 7 days, 1 day before deadline
  - [ ] Alert if overdue (deadline passed, no decision recorded)
  - [ ] Escalate to reviewer manager if overdue for 5+ days
  - [ ] API endpoint: GET /api/woo/bezwaren with filters (status, deadlineAt range)

- [ ] Implement bezwaar decision workflow (REQ-WPP-16)
  - [ ] UI: Record decision with fields:
    - decision (gegrond | ongegrond | ingetrokken | beroep-pending)
    - decisionDate
    - decisionDocument (optional, reference to decision letter)
    - reasoning/notes (text)
  - [ ] Digital signature required from approver
  - [ ] Trigger actions per decision type (see below)
  - [ ] Status transitions accordingly
  - [ ] API endpoint: PATCH /api/woo/bezwaren/{id}/decide

---

## Phase 10: Bezwaar Decision Outcomes

- [ ] Implement ongegrond (not upheld) outcome
  - [ ] Status: "ongegrond"
  - [ ] WooPublication remains "live" (no action)
  - [ ] Decision letter generated (template-driven)
  - [ ] Audit log: record decision + reason

- [ ] Implement gegrond (upheld) outcome → Withdrawal (REQ-WPP-16, REQ-WPP-17)
  - [ ] Status: "gegrond"
  - [ ] WooPublication status transitions to "withdrawn"
  - [ ] Trigger withdrawal process (see Phase 11)
  - [ ] Decision letter generated with reference to withdrawal

- [ ] Implement ingetrokken (withdrawn by applicant) outcome
  - [ ] Status: "ingetrokken"
  - [ ] WooPublication remains "live" (no action)
  - [ ] Audit log: record the withdrawal of the bezwaar

- [ ] Implement re-redaction outcome (REQ-WPP-16)
  - [ ] If bezwaar type = "wrong-redaction"
  - [ ] Trigger re-anonymisation check with updated rule set
  - [ ] Reviewer approves new redaction
  - [ ] New WooAnonymisationCheck record created
  - [ ] New PDF/A-2 generated
  - [ ] Resubmit to PLOOI with new koopReference
  - [ ] Status: "resolved-via-update"

---

## Phase 11: Withdrawal Workflow

- [ ] Implement withdrawal initiation (REQ-WPP-17)
  - [ ] When WooPublication transitions to "withdrawn":
    - Remove document from publication UI
    - Prepare tombstone object per PLOOI spec
    - Include: original koopReference, withdrawalDate, withdrawalReason, link to bezwaar decision (if applicable)
  - [ ] Store tombstone for submission

- [ ] Implement tombstone submission to PLOOI (REQ-WPP-17)
  - [ ] Use OpenConnector to submit tombstone to PLOOI withdrawal endpoint
  - [ ] PLOOI marks original publication "De-published"
  - [ ] Citizens see: "This publication has been withdrawn" + reason
  - [ ] publishedUrl becomes inactive
  - [ ] Status transition: withdrawn (complete)

- [ ] Implement withdrawal failure and retry (REQ-WPP-17)
  - [ ] If tombstone submission fails: apply exponential backoff retries
  - [ ] If final failure: escalate to WOO-coordinator for manual intervention
  - [ ] Audit log: record all withdrawal attempts

- [ ] Implement withdrawal audit trail (REQ-WPP-17)
  - [ ] Record in audit log:
    - Original publication date
    - Withdrawal date
    - Withdrawal reason (e.g., "Bezwaar gegrond")
    - Reference to related bezwaar
    - Who approved the withdrawal

---

## Phase 12: Publication Linking & Storage

- [ ] Implement publication URL storage on document (REQ-WPP-18)
  - [ ] When WooPublication reaches "live": koopReference + publishedUrl are obtained from PLOOI
  - [ ] Store publishedUrl as property on original docudesk Document object
  - [ ] Add badge in document viewer: "Published WOO" with clickable link
  - [ ] Show publication date, category, and any active bezwaar status

- [ ] Implement version history linking (REQ-WPP-18, REQ-WPP-25)
  - [ ] If document is republished (version 2): link both koopReferences
  - [ ] Show version chain: "Published (v1: withdrawn) → Published (v2: live)"
  - [ ] Provide links to both versions for historical reference

---

## Phase 13: Deadline Alerting

- [ ] Implement publication deadline tracking (REQ-WPP-19)
  - [ ] Calculate deadline per WooCategory.publishWithinDays
  - [ ] For each unpublished document: deadline = event_date + publishWithinDays
  - [ ] Store deadline on WooPublication record (or derive from category + event date)

- [ ] Implement deadline alerting (REQ-WPP-19)
  - [ ] Daily job: scan for approaching/overdue deadlines
  - [ ] Alert at: 30 days, 14 days, 7 days, 3 days, 1 day before deadline
  - [ ] Alert if overdue (status not "live" and deadline passed)
  - [ ] Coordinator dashboard: show overdue publications (red flag)
  - [ ] Email notifications to WOO-coordinator and manager (on escalation)

---

## Phase 14: Bulk Publication Wizard

- [ ] Implement bulk selection UI (REQ-WPP-20)
  - [ ] Filters: Category (dropdown), Date range (from/to), Publication status (checkbox)
  - [ ] Results: paginated list of matching documents (e.g., "Found 127 raadsstukken")
  - [ ] Checkbox column: select individual documents or "Select All"
  - [ ] Count display: "Selected: 105 documents"

- [ ] Implement bulk anonymisation check (REQ-WPP-20)
  - [ ] Iterate through selected documents
  - [ ] For each: run anonymisation detection
  - [ ] Store WooAnonymisationCheck per document
  - [ ] Progress bar: "Processing 1 of 105…"
  - [ ] Results aggregation: "87 ready, 18 need review"

- [ ] Implement bulk review and approval (REQ-WPP-20)
  - [ ] Group documents by anonymisation status (approved, needs-review, failed)
  - [ ] Reviewer can bulk-approve or review individually
  - [ ] Approve findings in batch or per-document mode

- [ ] Implement bulk submission to PLOOI (REQ-WPP-20)
  - [ ] Queue bulk submission job
  - [ ] Submit documents sequentially (rate limiting for PLOOI)
  - [ ] Progress dashboard: Total / Submitted / Pending / Failed counts
  - [ ] Real-time updates per document status
  - [ ] Retry mechanism for failed submissions

---

## Phase 15: Organisation-Specific Rule Sets

- [ ] Implement rule set management (REQ-WPP-21)
  - [ ] Admin UI: Configure rule sets per organisation type
  - [ ] Preset rule sets: "Gemeente", "Rijk", "Waterschap"
  - [ ] Customizable keyword lists per rule set
  - [ ] Detection thresholds (confidence score min/max)
  - [ ] Rule set versioning: nl-gemeente-2026-05, nl-gemeente-2026-06, etc.

- [ ] Implement rule set assignment (REQ-WPP-21)
  - [ ] Per-WooPublication: store which rule set was applied (ruleSetVersion)
  - [ ] Audit trail: if rule set is updated, shows which publications used old version
  - [ ] Bulk rule set upgrade: apply new rule set to existing publications (re-run anonymisation)

---

## Phase 16: Exemption Weighting Assistant

- [ ] Implement AVG-vs-WOO weighing assist (REQ-WPP-27)
  - [ ] Detect when a publication contains personal data
  - [ ] On GDPR erasure request: flag affected WooPublication records
  - [ ] UI: Show AVG right vs. WOO obligation with weighing checklist
    - Is data still necessary for original purpose?
    - Is continued publication in public interest?
    - Are less-intrusive alternatives (anonymisation) available?
  - [ ] Provide case-law recommendations
  - [ ] Legal team records decision: withdraw, re-anonymise, or keep as-is
  - [ ] Audit log: record decision + reasoning

---

## Phase 17: Annual WOO Report Generation

- [ ] Implement annual report aggregation (REQ-WPP-28)
  - [ ] At year-end, query all WooPublication records for the year
  - [ ] Aggregate metrics:
    - Total publications
    - By category (count per code 1–17)
    - Average latency per category (from event_date to live_date)
    - Bezwaar outcomes: received, ongegrond, gegrond, ingetrokken
    - Exemptions declared (by article)
    - PLOOI rejections (count + reasons)
  - [ ] Export format: PDF (for official submission), CSV, JSON

- [ ] Implement report interface (REQ-WPP-28)
  - [ ] WOO-coordinator can generate report for any year
  - [ ] Preview before finalizing
  - [ ] Digital signature (signing officer: WOO-coordinator)
  - [ ] Version control: save generated reports in archive

- [ ] Implement IOBJ audit trail integrity (REQ-WPP-28)
  - [ ] IOBJ can verify report counts against OpenRegister audit log
  - [ ] API endpoint: GET /api/woo/report/{year}/audit-trail
  - [ ] Cross-check hash integrity (hash-before/hash-after for tamper evidence)

---

## Phase 18: Integration with OpenCatalogi

- [ ] Implement OpenCatalogi sync (REQ-WPP-26)
  - [ ] When WooPublication reaches "live": sync to opencatalogi
  - [ ] Create catalogue record with WooPublication data (title, category, date, links)
  - [ ] Include both local copy reference and PLOOI URL
  - [ ] Sync API call: POST /opencatalogi/api/datasets (DIWOO profile)

- [ ] Implement DIWOO sitemap generation (REQ-WPP-26)
  - [ ] opencatalogi/woo-compliance app generates XML sitemap from WooPublication records
  - [ ] Sitemap location: /.well-known/diwoo-sitemap.xml
  - [ ] Include all live publications with PLOOI URLs + metadata
  - [ ] Robots.txt directives for search engine harvesting

- [ ] Implement organisation DIWOO portal (REQ-WPP-26)
  - [ ] UI: Browse/filter published WOO documents (sourced from WooPublication → OpenCatalogi)
  - [ ] Filter by category, date, status
  - [ ] Links to official PLOOI pages
  - [ ] SEO: ensure pages are crawlable and indexed

---

## Phase 19: Testing & Quality Assurance

- [ ] Unit tests
  - [ ] WooCategory schema validation
  - [ ] WooPublication state machine (valid/invalid transitions)
  - [ ] WooAnonymisationCheck finding storage and retrieval
  - [ ] Status transition audit log immutability
  - [ ] Hash computation (SHA-256 before/after)
  - [ ] DIWOO-XML generation and validation per category
  - [ ] Bezwaar deadline calculation

- [ ] Integration tests
  - [ ] End-to-end publication flow: initiate → categorise → anonymise → review → submit → live
  - [ ] PLOOI submission + retry logic
  - [ ] Bezwaar intake → decision → withdrawal
  - [ ] Exemption creation + publication blocking
  - [ ] Bulk publication workflow
  - [ ] OpenCatalogi sync

- [ ] Security tests
  - [ ] mTLS certificate validation for PLOOI submission
  - [ ] Audit log immutability (cannot be edited/deleted)
  - [ ] Redaction validation (no OCR recovery of scrubbed text)
  - [ ] PDF metadata stripping verification
  - [ ] Hash tampering detection

- [ ] Compliance tests (IOBJ)
  - [ ] Annual report accuracy (cross-check against audit log)
  - [ ] Audit trail completeness (all status transitions recorded)
  - [ ] Bezwaar deadline tracking correctness
  - [ ] Exemption dokumentation (belangenafweging recorded)

---

## Phase 20: Documentation & Deployment

- [ ] Write API documentation
  - [ ] OpenAPI spec for all WOO endpoints
  - [ ] Examples: publication initiation, category confirmation, anonymisation, bezwaar intake

- [ ] Write admin guide
  - [ ] WooCategory setup and updates
  - [ ] Rule set configuration (per organisation type)
  - [ ] PLOOI mTLS certificate setup
  - [ ] Annual report generation

- [ ] Write user guide (WOO-coordinator)
  - [ ] Publication workflow walkthrough
  - [ ] Category suggestion + confirmation
  - [ ] Anonymisation findings review
  - [ ] Deadline alerting and escalation
  - [ ] Bezwaar handling

- [ ] Write legal/compliance guide
  - [ ] Exemption decision process (WOO art. 5.1/5.2 + belangenafweging)
  - [ ] Bezwaar procedure (Awb deadlines)
  - [ ] IOBJ audit trail requirements
  - [ ] AVG-vs-WOO weighing

- [ ] Deploy to staging
  - [ ] Run full integration test suite
  - [ ] Perform security audit (penetration testing)
  - [ ] Load testing (bulk publication, concurrent bezwaar intake)
  - [ ] Smoke test with real PLOOI credentials (test environment)

- [ ] Deploy to production
  - [ ] Plan rollout (can be phased by organisation type or region)
  - [ ] Training: WOO-coordinators, reviewers, legal teams
  - [ ] Monitor audit logs for early issues
  - [ ] Support: escalation path for PLOOI submission errors
