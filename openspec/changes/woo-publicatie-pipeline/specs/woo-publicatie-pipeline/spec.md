---
status: proposed
---

# WOO Publicatie Pipeline

## Purpose

Automates end-to-end Wet Open Overheid (WOO) publication: from document intake through category assignment, anonymisation checking, PDF/A-2 + DIWOO-XML generation, submission to PLOOI (the national publication platform), status tracking, and bezwaar (objection) workflow. Every step is audited and reversible via OpenRegister; the pipeline ensures compliant, traceable publication of the 17 mandatory information categories on schedule with full redaction proof.

## Relation to Existing Specs

- **docudesk/anonymization** — provides the detection and redaction engine; this spec reuses it with WOO-specific rule sets and publication-grade redaction strength
- **docudesk/anonymization-entity-review** — reused for the reviewer approval gate before PLOOI submission
- **docudesk/archiefwet-retention-engine** — published documents gain extended retention; cannot be destroyed without formal de-publication
- **docudesk/template-management** — bezwaar decision and publication confirmation letters are template-driven
- **OpenConnector** — handles the mTLS, retry logic, and async polling for PLOOI Aanleverkanaal (intake API)
- **opencatalogi/woo-compliance** — consumes WooPublication records to populate the DIWOO sitemap; this spec produces the records
- **zaakafhandelapp / openzaak** — most publishable documents originate in zaken; the pipeline reads zaaktype and publication flags

## Requirements

### REQ-WPP-01: Auto-Suggest WOO Category (Priority: Must)

The system suggests a WOO information category per document using metadata (zaaktype, subject, date) and (optionally) document content classification. The suggestion is shown to the coordinator for confirmation or override.

#### Scenario: Suggest category for council documents (raadsstukken)
- GIVEN a document exists in docudesk with `zaaktype = "raadsstuk"` and `date >= 2026-05-18`
- AND the WooCategory "Raadsstukken" (code 5) exists with published status
- WHEN the user initiates "publish-WOO" action
- THEN the system suggests `wooCategory = "woo-cat-05"` (Raadsstukken)
- AND the suggestion appears in the UI for coordinator review

#### Scenario: Suggest category for annual reports (jaarplannen)
- GIVEN a document contains `subject ~ "jaarplan"` or `zaaktype = "jaarplan"`
- WHEN category suggestion runs
- THEN the system suggests `wooCategory = "woo-cat-04"` (Jaarplannen)
- AND the confidence score is shown (0.0–1.0)

#### Scenario: Fallback to catch-all category
- GIVEN a document's metadata does not match any of the 16 specific categories
- WHEN category suggestion runs
- THEN the system suggests `wooCategory = "woo-cat-17"` (Overige informatie van openbaar belang)
- AND a note appears: "Category suggestion uncertain; please review"

#### Scenario: Category suggestion on multi-zaak document
- GIVEN a single document references multiple zaaktype values
- WHEN category suggestion runs
- THEN the system returns the most common zaaktype's category, or lists all candidates for manual selection

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-001 | Auto-suggest WOO category per document using metadata + content classifier | MUST | Proposed |

---

### REQ-WPP-02: Confirm-or-Override Category with Justification (Priority: Must)

The coordinator confirms the suggested category or overrides it with mandatory written justification (stored in the audit log).

#### Scenario: Confirm suggested category
- GIVEN the system suggests `wooCategory = "woo-cat-05"` for a raadsstuk
- WHEN the coordinator clicks "Confirm"
- THEN the WooPublication record is created with that category
- AND the audit log records: `action=category_confirmed, suggestedCategory=woo-cat-05, confirmedBy=user-xyz, at=timestamp`

#### Scenario: Override category with justification
- GIVEN the system suggests `wooCategory = "woo-cat-04"` for a document
- AND the coordinator determines it is actually `wooCategory = "woo-cat-06"` (Bestuurlijke besluiten)
- WHEN the coordinator selects "woo-cat-06" and enters justification: "Dit is een raadsbesluit, niet een jaarplan"
- THEN the WooPublication record is created with `wooCategory = "woo-cat-06"`
- AND the audit log records: `action=category_override, suggestedCategory=woo-cat-04, overriddenCategory=woo-cat-06, justification="Dit is een raadsbesluit, niet een jaarplan", overriddenBy=user-xyz, at=timestamp`

#### Scenario: Override rejected without justification
- GIVEN the coordinator attempts to override the category
- AND no justification text is entered
- WHEN they click "Save"
- THEN a 400 error appears: "Override requires justification (min 10 characters)"
- AND the WooPublication is not created

#### Scenario: Cancel category assignment
- GIVEN the coordinator is reviewing the suggested category
- WHEN they click "Cancel" without saving
- THEN the publication initiation is aborted
- AND no WooPublication record is created

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-002 | Confirm-or-override WOO category workflow with mandatory justification on override | MUST | Proposed |

---

### REQ-WPP-03: All 17 WOO Information Categories (Priority: Must)

All 17 WOO information categories (per WOO art. 3.3) are implemented as first-class WooCategory data with legal references, metadata requirements, and publication deadlines.

#### Scenario: WooCategory data for raadsstukken
- GIVEN the system is initialized
- WHEN the admin checks the WooCategory register
- THEN WooCategory with code="5", titleNl="Raadsstukken" exists
- AND it includes `wettelijkeGrondslag="WOO art. 3.3 sub e"`
- AND `publishWithinDays=7` (raadsstukken must be published within one week of the meeting)
- AND `checklistItems` includes: [Vergaderdatum, Agendapunt, Deelnemers, Openbare-status]

#### Scenario: WooCategory for covenanten
- GIVEN WooCategory for covenanten is queried
- WHEN the record is retrieved
- THEN code="1", titleNl="Covenanten"
- AND `publishWithinDays=30`
- AND `publicationFrequency="continuous"`

#### Scenario: All 17 categories present
- GIVEN the system is in production
- WHEN WooCategory.findAll() is called
- THEN exactly 17 records are returned (codes 1–17)
- AND each has non-null: code, wettelijkeGrondslag, titleNl, descriptionNl, publishWithinDays, publicationFrequency

#### Scenario: Category metadata mapping for DIWOO
- GIVEN WooCategory for raadsstukken is queried
- WHEN the koopMetadataMapping is checked
- THEN it specifies which DIWOO fields are required, e.g.:
  - `dct:title` → required
  - `dcterms:issued` → required
  - `dcat:eventDate` → required
  - `dcat:agendaItem` → required

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-003 | All 17 WOO information categories implemented as first-class data with legal references | MUST | Proposed |

---

### REQ-WPP-04: Per-Document Anonymisation Check (Priority: Must)

The system scans each document for personally identifiable data and other sensitive patterns before publication, producing a structured findings list.

#### Scenario: Detect BSN (Burgerservicenummer)
- GIVEN a PDF contains the string "123.45.678" (example BSN format)
- WHEN anonymisation check runs
- THEN a finding is created with:
  - `ruleId="BSN-DETECT"`
  - `severity="critical"`
  - `snippet="123.45.678"`
  - `action="redact"`
- AND the location (page + line) is recorded

#### Scenario: Detect multiple PII types
- GIVEN a document contains:
  - A BSN: "123.45.678"
  - An e-mail: "j.smith@example.com"
  - A phone number: "+31 (0)20 1234567"
- WHEN anonymisation check runs
- THEN three separate findings are created, one per entity type
- AND each finding includes the snippet and location

#### Scenario: Detect bedrijfsgevoelig (trade secrets)
- GIVEN a document contains the word "bedrijfsgeheim" or "vertrouwelijke prijsstelling"
- WHEN anonymisation check runs with rule set that includes keyword detection
- THEN a finding is created with `ruleId="BEDRIJFSGEVOELIG"` and `severity="medium"`

#### Scenario: Detect beveiligingsrubricering (security classification)
- GIVEN a document is marked with classification "staatsgeheim" or "departementaal vertrouwelijk"
- WHEN anonymisation check runs
- THEN a finding is created with `ruleId="CLASSIFIED"` and `severity="critical"`
- AND the publication is flagged for manual review (cannot auto-publish classified documents)

#### Scenario: Clean document (no findings)
- GIVEN a document contains no PII, trade secrets, or classified content
- WHEN anonymisation check runs
- THEN the check completes with `findings=[]`
- AND `status="approved"` (no redaction needed)

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-004 | Per-document anonymisation check: BSN, BIN, RSIN, IBAN, e-mail, phone, address, persoonsgegevens, bedrijfsgevoelig, beveiligingsrubricering | MUST | Proposed |

---

### REQ-WPP-05: Structured Findings with Page References (Priority: Must)

Anonymisation findings include precise location references (page number, line, coordinate) for the reviewer to assess redaction.

#### Scenario: Finding includes page and line location
- GIVEN a finding for "123.45.678" is created
- WHEN the WooAnonymisationCheck record is examined
- THEN each finding includes `locationRef` in format `page:N,line:M` or `page:N,x:X,y:Y` (pixel coordinates)
- AND the reviewer can jump directly to the location in the document viewer

#### Scenario: Multiple findings on same page
- GIVEN a page contains two BSNs and one e-mail address
- WHEN the check completes
- THEN three findings are created, each with distinct `locationRef` values pointing to the same page
- AND they are sorted by location (top to bottom)

#### Scenario: Coordinate-based location for scanned PDFs
- GIVEN a scanned PDF (image-based) contains a BSN
- WHEN OCR + entity detection runs
- THEN the finding includes `locationRef="page:3,x:150,y:200"` (pixel coordinates from OCR confidence bounds)

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-005 | Anonymisation check produces a structured findings list with page+coordinate refs | MUST | Proposed |

---

### REQ-WPP-06: Redaction with Visible Boxes + Text-Layer Scrubbing (Priority: Must)

Redaction applies both visual (black boxes) and text-layer scrubbing to prevent OCR/copy-paste recovery.

#### Scenario: Apply black box redaction
- GIVEN a finding for "123.45.678" at location `page:1,x:100,y:150` with width 80pt, height 12pt
- WHEN redaction is applied
- THEN the output PDF contains a black rectangle covering the BSN
- AND a viewer cannot see the underlying text

#### Scenario: Text-layer scrubbing prevents OCR recovery
- GIVEN the same document with redacted BSN
- WHEN a user copies text from the redacted area
- THEN no text is returned (text-layer is empty)
- AND OCR tools cannot recover the original value

#### Scenario: Redaction preserves document structure
- GIVEN a page with multiple redactions
- WHEN redaction is applied
- THEN page layout, images, and other content remain intact
- AND only the specific entities are redacted

#### Scenario: Partial paragraph redaction
- GIVEN a paragraph contains two sentences, only the second containing PII
- WHEN redaction is applied
- THEN only the PII-containing portion is redacted
- AND the rest of the paragraph remains readable

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-006 | Redaction applies BOTH visible black boxes AND text-layer scrubbing (not just visual cover) | MUST | Proposed |

---

### REQ-WPP-07: Strip PDF Metadata + Thumbnails + Form Fields (Priority: Must)

Redacted output strips metadata, thumbnails, embedded files, form fields, and comments to ensure publication-grade cleanliness.

#### Scenario: Remove document metadata
- GIVEN a source document contains:
  - Document title (metadata)
  - Creation date (metadata)
  - Author (metadata)
  - Keywords (metadata)
- WHEN redaction is applied
- THEN the output PDF has all metadata stripped
- AND checking `pdfinfo` shows empty/default values for title, author, creation date

#### Scenario: Remove embedded thumbnails
- GIVEN a PDF has embedded page thumbnails
- WHEN redaction is applied
- THEN thumbnails are removed
- AND the output PDF size is smaller

#### Scenario: Remove form fields
- GIVEN a PDF form contains text fields and checkboxes
- WHEN redaction is applied
- THEN all form fields are converted to static text or removed
- AND users cannot interact with the form in the output

#### Scenario: Remove comments and annotations
- GIVEN a PDF has reviewer comments (Acrobat annotations)
- WHEN redaction is applied
- THEN all annotations are removed
- AND the output contains no comments

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-007 | Redaction strips PDF metadata + embedded thumbnails + form fields + comments | MUST | Proposed |

---

### REQ-WPP-08: Hash-Before + Hash-After for Tamper Evidence (Priority: Must)

Before and after hashes are captured to prove no tampering occurred between anonymisation check and publication.

#### Scenario: Capture hash on anonymisation check
- GIVEN a document is scanned for PII
- WHEN the check completes
- THEN the system computes SHA-256 hash of the original PDF
- AND stores it as `WooAnonymisationCheck.hashBefore`

#### Scenario: Capture hash after redaction
- GIVEN redaction is applied
- WHEN the redacted PDF is generated
- THEN the system computes SHA-256 hash of the redacted output
- AND stores it as `WooAnonymisationCheck.hashAfter`

#### Scenario: Hash validation by auditor
- GIVEN IOBJ samples a published document
- WHEN they request the audit trail
- THEN they can recompute the hashBefore + hashAfter
- AND verify that no tampering occurred (bit-for-bit comparison)

#### Scenario: Hash mismatch detection
- GIVEN a redacted PDF is tampered with after approval
- WHEN the auditor verifies the hash
- THEN `hashAfter` no longer matches the current document
- AND the tampering is detected

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-008 | Hash-before + hash-after captured for tamper evidence | MUST | Proposed |

---

### REQ-WPP-09: Reviewer Approval Required Before Submission (Priority: Must)

A human reviewer (DIV-medewerker / anonymisation expert) must approve the redaction before the document is submitted to PLOOI.

#### Scenario: Reviewer approves redaction
- GIVEN a document has been redacted and is ready for review
- AND a reviewer is assigned via `docudesk/anonymization-entity-review`
- WHEN the reviewer examines the redacted PDF and approves it
- THEN `WooAnonymisationCheck.reviewedBy` is set
- AND `WooAnonymisationCheck.reviewedAt` is recorded
- AND `WooAnonymisationCheck.status` transitions to "approved"

#### Scenario: Reviewer requests changes
- GIVEN the reviewer determines the redaction is incomplete (e.g., a BSN was missed)
- WHEN they reject the redaction
- THEN the status transitions to "needs-revision"
- AND the coordinator is notified to fix the issue

#### Scenario: Approval gating
- GIVEN a WooPublication is in "draft" status
- WHEN the WooAnonymisationCheck.status is not "approved"
- THEN the publication cannot transition to "queued" (ready for PLOOI submission)
- AND an error appears: "Redaction must be reviewer-approved before submission"

#### Scenario: Approval timeout
- GIVEN a redaction has been waiting for reviewer approval for 5 business days
- WHEN the deadline alert fires
- THEN the WOO-coordinator is notified: "Redaction approval pending for 5 days"

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-009 | Reviewer approval required before submission | MUST | Proposed |

---

### REQ-WPP-10: Output PDF/A-2 Compliant + DIWOO XML (Priority: Must)

The publication-ready output is PDF/A-2 (ISO 19005-2) for long-term preservation, accompanied by DIWOO-XML metadata.

#### Scenario: Generate PDF/A-2 output
- GIVEN a redacted document is approved
- WHEN the publication engine generates output
- THEN the output PDF complies with PDF/A-2 standard (ISO 19005-2)
- AND it includes XMP metadata specifying PDF/A-2 profile
- AND tools (e.g., veraPDF) validate the file as conforming PDF/A-2

#### Scenario: Metadata not embedded in PDF/A-2
- GIVEN PDF/A-2 is generated
- WHEN DIWOO metadata is needed (title, issuer, publication date, etc.)
- THEN the metadata is stored in a separate DIWOO-XML file
- AND the PDF includes a reference to the XML (e.g., via linked file identifier)
- AND both files are submitted together to PLOOI

#### Scenario: DIWOO-XML generation for raadsstukken
- GIVEN a publication has `wooCategory = "woo-cat-05"` (Raadsstukken)
- WHEN DIWOO-XML is generated
- THEN it includes mandatory fields:
  - `dct:title` (raadsstuk title)
  - `dcterms:issued` (publication date)
  - `dcat:eventDate` (meeting date)
  - `dcat:agendaItem` (reference to agenda point)
  - `dcatap:distribution` (link to the PDF/A-2 file)

#### Scenario: DIWOO metadata validation
- GIVEN DIWOO-XML is generated
- WHEN it is validated against the KOOP DIWOO schema
- THEN all required fields per the WooCategory are present
- AND all values conform to TOOI vocabularies and format specifications

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-010 | Output PDF/A-2 compliant + DIWOO XML metadata | MUST | Proposed |

---

### REQ-WPP-11: Submission to PLOOI via Aanleverkanaal API (Priority: Must)

The publication-ready PDF/A-2 and DIWOO-XML are submitted to KOOP's PLOOI platform via the Aanleverkanaal (intake) API with mTLS authentication.

#### Scenario: Successful PLOOI submission
- GIVEN a publication is in "queued" status (ready for PLOOI)
- AND the PDF/A-2 and DIWOO-XML are ready
- WHEN OpenConnector submits to PLOOI Aanleverkanaal API with:
  - mTLS certificate (organization credentials)
  - PDF/A-2 file (multipart upload)
  - DIWOO-XML metadata
  - HTTP POST to KOOP endpoint
- THEN PLOOI returns a 200/201 response
- AND a `koopReference` (e.g., "KOOP-2026-45782") is returned
- AND `WooPublication.status` transitions to "submitted"

#### Scenario: PLOOI submission with large file
- GIVEN a redacted PDF is 150 MB
- WHEN OpenConnector submits
- THEN it uses chunked/streaming upload (not all-at-once)
- AND timeouts are extended (e.g., 5 minutes per chunk)

#### Scenario: mTLS certificate validation
- GIVEN OpenConnector is configured with organization mTLS certificate
- WHEN it submits to PLOOI
- THEN the certificate is validated by PLOOI
- AND submission is rejected (400) if the cert is invalid or revoked

#### Scenario: Invalid DIWOO-XML rejection
- GIVEN the DIWOO-XML does not conform to KOOP schema
- WHEN PLOOI validates it
- THEN a 400 error is returned with details on the validation failure
- AND the submission is rejected; the coordinator is notified to fix metadata

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-011 | Submission to PLOOI/KOOP via the official intake API (Aanleverkanaal) | MUST | Proposed |

---

### REQ-WPP-12: Submission Retries with Exponential Backoff (Priority: Must)

If PLOOI submission fails (network error, timeout), the system retries with exponential backoff; final failures are escalated to the WOO-coordinator.

#### Scenario: Network error retry
- GIVEN a submission attempt fails with a network timeout
- WHEN the OpenConnector handler detects the timeout
- THEN it schedules a retry after 5 seconds
- AND subsequent retries are at 10, 20, 40 seconds (exponential backoff)
- AND max 5 retries are attempted before final escalation

#### Scenario: Temporary PLOOI outage
- GIVEN PLOOI returns HTTP 503 (service unavailable)
- WHEN the submission handler receives the 503
- THEN it interprets it as temporary (not permanent failure)
- AND schedules a retry with exponential backoff
- AND DOES NOT immediately escalate

#### Scenario: Permanent PLOOI rejection
- GIVEN all 5 retries have been exhausted
- WHEN the 5th retry fails
- THEN `WooPublication.status` transitions to "rejected"
- AND `WooPublication.rejectionReason` is set to the last error (e.g., "Invalid DIWOO metadata after 5 attempts")
- AND the WOO-coordinator is notified via email/dashboard alert

#### Scenario: Manual retry after fix
- GIVEN a publication is in "rejected" status due to invalid metadata
- WHEN the coordinator corrects the metadata and clicks "Retry"
- THEN the retry counter resets
- AND submission is reattempted with fresh exponential backoff

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-012 | Submission retries with exponential backoff and final-failure escalation | MUST | Proposed |

---

### REQ-WPP-13: Per-Publication Status Tracking with Audit Log (Priority: Must)

Each publication's status transitions are recorded in an immutable audit log for IOBJ oversight and compliance proof.

#### Scenario: Status transition audit log
- GIVEN a WooPublication transitions from "draft" to "queued"
- WHEN the transition occurs
- THEN an audit entry is created with:
  - `timestamp`
  - `fromStatus="draft"`
  - `toStatus="queued"`
  - `triggeredBy=user-xyz`
  - `reason="Reviewer approved redaction"` (if applicable)
- AND the entry is immutable (cannot be deleted or edited)

#### Scenario: Full state history retrieval
- GIVEN a published document's IOBJ audit is requested
- WHEN the auditor queries the publication's audit log
- THEN all state transitions are shown in chronological order:
  1. draft → queued (date/time/user)
  2. queued → submitted (date/time)
  3. submitted → accepted (date/time)
  4. accepted → live (date/time)
- AND timestamps are precise to the second

#### Scenario: Audit log includes rejection details
- GIVEN a submission was rejected
- WHEN the rejection is recorded
- THEN the audit log includes:
  - `reason="PLOOI returned: Invalid category code 99"`
  - `errorDetail` (full PLOOI response body)
  - `timestamp`

#### Scenario: Retention compliance via audit log
- GIVEN a publication is live on PLOOI
- WHEN retention policy requires proof of publication date
- THEN the auditor queries the audit log
- AND can produce the exact timestamp of the "accepted" → "live" transition as evidence

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-013 | Per-publication status tracking with full state-transition audit log | MUST | Proposed |

---

### REQ-WPP-14: KOOP Rejection Reasons Parsed and Surfaced (Priority: Must)

When PLOOI rejects a submission, the rejection reason is parsed and displayed to the coordinator for remediation.

#### Scenario: Parse metadata validation error
- GIVEN PLOOI rejects with: `{"error": "Field dcat:eventDate is required for category raadsstukken"}`
- WHEN the response is parsed
- THEN the error is extracted and shown to the coordinator as:
  - **Field**: dcat:eventDate
  - **Reason**: Required for raadsstukken
  - **Action**: Add meeting date to the publication
- AND the coordinator can click "Fix and Retry"

#### Scenario: Parse PDF validation error
- GIVEN PLOOI rejects with: `{"error": "PDF is not PDF/A-2 compliant (missing XMP metadata)"}`
- WHEN the error is parsed
- THEN it is displayed as:
  - **Issue**: PDF/A-2 compliance failure
  - **Detail**: XMP metadata missing
  - **Action**: Regenerate PDF with PDF/A-2 encoding

#### Scenario: Error logged and alerting
- GIVEN rejection reason is parsed
- WHEN the coordinator views the publication
- THEN the rejection reason is prominently displayed (red background/alert icon)
- AND an email alert is sent: "Publication [title] rejected: [reason]"

#### Scenario: Unknown rejection code
- GIVEN PLOOI returns a new error code not yet in the parser
- WHEN the error is parsed
- THEN a fallback message is shown:
  - **Error Code**: [code]
  - **Raw Response**: [full PLOOI response]
  - **Contact KOOP Support**: [link]

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-014 | KOOP rejection reasons parsed and surfaced in UI | MUST | Proposed |

---

### REQ-WPP-15: Bezwaar Workflow with Legal Deadline Tracking (Priority: Must)

If a citizen or affected party objects to a publication (bezwaar), the system tracks the legal deadline (default 6 weeks per Awb) for the organisation to respond.

#### Scenario: Bezwaar received and tracked
- GIVEN a citizen submits a bezwaar to the organisation
- AND the organisation records it in the system linking to the WooPublication
- WHEN a WooBezwaar record is created
- THEN it includes:
  - `bezwaarmaker` (party name)
  - `bezwaarType` (e.g., "wrong-redaction")
  - `submittedAt` (timestamp)
  - `deadlineAt` (submittedAt + 42 days per Awb 7:1)
  - `status="received"`

#### Scenario: Deadline approaching alert
- GIVEN a bezwaar's deadline is 5 days away
- WHEN the daily alert job runs
- THEN the assigned reviewer is notified:
  - **Bezwaar deadline in 5 days**
  - **Bezwaar type**: [type]
  - **Bezwaarmaker**: [name]
  - **Decision required by**: [date]

#### Scenario: Deadline overdue
- GIVEN a bezwaar's `deadlineAt` has passed
- AND no decision has been recorded
- WHEN the daily alert job runs
- THEN the status is escalated to "overdue"
- AND the reviewer and WOO-coordinator are notified

#### Scenario: Multiple bezwaar on same publication
- GIVEN a publication has 3 separate bezwaar submissions
- WHEN each is recorded
- THEN each has its own WooBezwaar record
- AND each tracks its own deadline independently

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-015 | Bezwaar workflow with legal deadline tracking (default 6 weeks per Awb) | MUST | Proposed |

---

### REQ-WPP-16: Bezwaar Decision Can Trigger Withdrawal/Update/Reclassification (Priority: Must)

The legal team's decision on a bezwaar can result in: upholding the publication (ongegrond), withdrawing it (gegrond), or updating the redaction/category (ingetrokken + resubmission).

#### Scenario: Bezwaar ongegrond (not upheld) — publication stands
- GIVEN a bezwaar is reviewed and determined to be without merit
- WHEN the reviewer records `decision="ongegrond"`
- THEN:
  - `WooBezwaar.status` transitions to "ongegrond"
  - `WooBezwaar.decisionAt` is recorded
  - `WooPublication.status` remains "live"
  - The publication remains on PLOOI unchanged

#### Scenario: Bezwaar gegrond (upheld) — withdrawal
- GIVEN a bezwaar claims "publication-should-not-have-happened"
- AND the review determines the bezwaar is valid
- WHEN the reviewer records `decision="gegrond"` with reason
- THEN:
  - `WooBezwaar.status` transitions to "gegrond"
  - `WooPublication.status` transitions to "withdrawn"
  - The withdrawal process is initiated (REQ-WPP-17)
  - A decision letter is generated (template-driven)

#### Scenario: Bezwaar ingetrokken (withdrawn by applicant)
- GIVEN the bezwaarmaker withdraws their objection
- WHEN the organisation records it
- THEN `WooBezwaar.status` transitions to "ingetrokken"`
- AND the publication remains live (no further action needed)

#### Scenario: Bezwaar leads to re-redaction
- GIVEN a bezwaar claims "wrong-redaction" (e.g., PII was not fully redacted)
- AND the review confirms this
- WHEN the reviewer updates the redaction
- THEN:
  - A new WooAnonymisationCheck is created
  - The redaction is reapproved
  - The publication is resubmitted to PLOOI with a new version
  - `WooBezwaar.status` transitions to "resolved-via-update"`

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-016 | Bezwaar decision can trigger withdrawal, partial-redaction-update, or re-classification | MUST | Proposed |

---

### REQ-WPP-17: Withdrawal Pushes Tombstone to PLOOI (Priority: Must)

When a publication is withdrawn (per bezwaar decision or other reason), a tombstone is submitted to PLOOI to inform the public that the document has been de-published.

#### Scenario: Withdrawal initiation
- GIVEN a `WooPublication.status` transitions to "withdrawn"
- WHEN the withdrawal is recorded
- THEN:
  - The document is removed from docudesk publication view
  - A tombstone object is prepared per PLOOI withdrawal API spec
  - The tombstone includes:
    - Original `koopReference`
    - `withdrawalDate` (today)
    - `withdrawalReason` (e.g., "Bezwaar gegrond")
    - Link to bezwaar decision document (if applicable)

#### Scenario: Tombstone submission to PLOOI
- GIVEN the tombstone is ready
- WHEN OpenConnector submits it to PLOOI withdrawal endpoint
- THEN:
  - PLOOI acknowledges the tombstone
  - The document on open.overheid.nl is marked "De-published"
  - The public can see the withdrawal reason
  - `WooPublication.publishedUrl` is no longer active

#### Scenario: Withdrawal failure and retry
- GIVEN the tombstone submission to PLOOI fails
- WHEN the retry mechanism engages
- THEN:
  - Exponential backoff retries are applied
  - If final retry fails, the WOO-coordinator is escalated
  - Manual submission option is provided

#### Scenario: Historical record of withdrawal
- GIVEN a publication is withdrawn
- WHEN the audit trail is reviewed
- THEN the audit log shows:
  - Original publication date
  - Withdrawal date
  - Withdrawal reason
  - Reference to the bezwaar (if applicable)
  - Who approved the withdrawal

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-017 | Withdrawal pushes a tombstone to PLOOI per their withdrawal API | MUST | Proposed |

---

### REQ-WPP-18: Per-Publication Permalink Stored on Document (Priority: Must)

After successful PLOOI publication, the public URL (open.overheid.nl) is stored back on the original document for easy citizen discovery.

#### Scenario: Store open.overheid.nl URL
- GIVEN a publication reaches "live" status on PLOOI
- WHEN PLOOI confirms the publication is public
- THEN:
  - `WooPublication.publishedUrl` is set (e.g., "https://open.overheid.nl/dataset/KOOP-2026-45782")
  - The URL is also stored as a property/field on the original docudesk Document object
  - A link appears in the document viewer: "View published version on open.overheid.nl"

#### Scenario: Document metadata includes publication link
- GIVEN a document was published under WOO
- WHEN the document is viewed in docudesk
- THEN:
  - A badge/indicator shows "Published WOO"
  - The published URL is clickable
  - Metadata shows publication date, category, and any bezwaar status

#### Scenario: Unpublished document has no URL
- GIVEN a document is marked for publication but is still in "draft" status
- WHEN the document is viewed
- THEN no publication URL is shown
- AND the UI shows: "Pending publication" or "Publication in progress"

#### Scenario: Version history with URLs
- GIVEN a document is published, then withdrawn, then republished (after re-redaction)
- WHEN the document's publication history is viewed
- THEN:
  - Version 1: Published KOOP-2026-45782 (withdrawn)
  - Version 2: Published KOOP-2026-45783 (live)
  - Both URLs are retained for historical reference

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-018 | Per-publication permalink (open.overheid.nl) stored back on the document | MUST | Proposed |

---

### REQ-WPP-19: Publication Deadline Alerting (Priority: Should)

The system alerts the WOO-coordinator when a document is overdue for publication based on its WOO category deadline.

#### Scenario: Alert for overdue raadsstukken
- GIVEN a document is a raadsstuk from a meeting on 2026-05-18
- AND WooCategory code 5 specifies `publishWithinDays=7`
- AND the deadline is 2026-05-25
- WHEN today is 2026-05-26 (1 day overdue)
- THEN an alert appears in the coordinator's dashboard:
  - **Overdue publication**
  - **Document**: Raadsagenda 18 mei
  - **Deadline was**: 2026-05-25
  - **Overdue by**: 1 day
  - **Action**: Click to initiate publish workflow

#### Scenario: Deadline approaching alert
- GIVEN a document's publication deadline is 3 days away
- WHEN the daily alert job runs
- THEN the coordinator is notified:
  - **Publication deadline in 3 days**
  - **Document**: [title]
  - **Deadline**: [date]

#### Scenario: Alert escalation to manager
- GIVEN an overdue publication has been unactioned for 5 days
- WHEN the alert job runs
- THEN the escalation rule triggers
- AND the manager/WOO-coordinator's manager is notified
- AND the alert visibility is increased (e.g., colored red, pinned to top)

#### Scenario: Alert dismissal on publication
- GIVEN a publication is initiated for an overdue document
- WHEN the document transitions to "live"
- THEN the alert is automatically cleared
- AND the document is removed from the overdue list

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-019 | Publication deadline alerting (overdue WOO obligation per category) | SHOULD | Proposed |

---

### REQ-WPP-20: Bulk-Publication Wizard (Priority: Should)

A wizard allows the coordinator to select multiple documents (e.g., all raadsstukken from the past 2 years) and initiate bulk publication workflow.

#### Scenario: Bulk selection by category and date range
- GIVEN the coordinator wants to publish raadsstukken from 2024–2026
- WHEN they open the bulk wizard
- AND select:
  - Category: "Raadsstukken"
  - Date range: 2024-01-01 to 2026-05-31
- THEN the system displays: "Found 127 unpublished raadsstukken"
- AND offers a preview list with checkbox selection

#### Scenario: Bulk preview and filtering
- GIVEN the preview list shows 127 raadsstukken
- WHEN the coordinator views the list
- THEN they can:
  - Filter by date, title, or publication status
  - Checkbox-select specific documents (or select all)
  - Exclude already-published items
- AND a count shows "Selected: 105 documents"

#### Scenario: Bulk anonymisation check initiation
- GIVEN 105 documents are selected
- WHEN the coordinator clicks "Start anonymisation check"
- THEN:
  - A bulk job is queued
  - Progress bar shows "Processing 1 of 105…"
  - Each document runs through the anonymisation pipeline
  - Results are aggregated per document

#### Scenario: Bulk review and approval
- GIVEN all 105 documents have been anonymisation-checked
- WHEN the coordinator reviews the findings
- THEN:
  - A summary shows: "87 documents ready to publish, 18 need redaction review"
  - Documents are grouped by status
  - Reviewer can approve findings in bulk or individual-document mode

#### Scenario: Bulk submission to PLOOI
- GIVEN approval is complete
- WHEN the coordinator clicks "Submit all to PLOOI"
- THEN:
  - A bulk submission job is queued
  - OpenConnector submits documents sequentially (not in parallel to avoid PLOOI rate limits)
  - Progress tracking shows real-time submission status per document
  - Failures are logged and retried per exponential backoff

#### Scenario: Bulk publication dashboard
- GIVEN a bulk job is in progress
- WHEN the coordinator views the job details
- THEN:
  - Total: 105
  - Submitted: 87
  - Pending: 18
  - Failed: 0
  - Est. completion: [time]

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-020 | Bulk-publication wizard for backlog (e.g. raadsstukken last 2 years) | SHOULD | Proposed |

---

### REQ-WPP-021: Organisation-Specific Anonymisation Rule Sets (Priority: Should)

Anonymisation rule sets can vary per organisation type (gemeenten / Rijk / waterschappen) with configurable keyword lists and detection thresholds.

#### Scenario: Rule set configuration per organisation type
- GIVEN a municipality is using docudesk
- WHEN the admin configures anonymisation rules
- THEN they can select a preset: "Gemeente rule set"
- AND the system loads keyword lists, detection models, and redaction thresholds optimized for municipalities

#### Scenario: Customize keyword detection
- GIVEN the rule set includes keyword detection for "bedrijfsgevoelig"
- WHEN the admin views the rule set
- THEN they can add custom keywords:
  - "vertrouwelijke prijsstelling"
  - "concurrentiegevoelig"
  - "propriëtair"
- AND these are added to the detection engine

#### Scenario: Rule set versioning
- GIVEN anonymisation rule set "nl-gemeente-2026-05" is in use
- WHEN KOOP releases updated DIWOO guidance
- THEN a new version "nl-gemeente-2026-06" is available
- AND the admin can review the diff and upgrade on a schedule

#### Scenario: Rule set per document type
- GIVEN an organisation wants stricter redaction for personnel files vs. council documents
- WHEN they configure rule sets
- THEN they can assign:
  - Rule set A: "Strict (personnel)" for internal documents
  - Rule set B: "Standard (governance)" for council docs
- AND each WooPublication specifies which rule set was applied

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-021 | Anonymisation rule sets per organisation type (gemeenten / Rijk / waterschappen) | SHOULD | Proposed |

---

### REQ-WPP-22: Exemption (Uitzonderingsgrond) Workflow (Priority: Must)

Legal teams can declare exemptions (WOO art. 5.1/5.2) with detailed belangenafweging (weighing test) to justify partial or full non-publication.

#### Scenario: Create exemption for trade secrets
- GIVEN a document contains bedrijfsgevoelige informatie (trade secrets)
- AND the legal team determines it meets WOO art. 5.1.c (bedrijfsgeheimen)
- WHEN they create a WooExemption record
- THEN they include:
  - `exemptionArticle: "WOO art. 5.1.c"`
  - `exemptionScope: "partial-page"` (only page 3 is exempted)
  - `justification: "Pagina 3 bevat vertrouwelijke prijsstellingen van onze leverancier…"`
  - `weighingTest: "Het belang van bescherming van bedrijfsgeheimen weegt zwaarder dan het belang van openbaarheid, omdat…"`
  - `decisionBy: "user-juridisch-001"`
  - `decisionDate: "2026-05-15"`

#### Scenario: Full document exemption
- GIVEN a document is classified "staatsgeheim" per Wbni
- AND the legal team determines it cannot be published at all
- WHEN they create a WooExemption
- THEN:
  - `exemptionScope: "full"`
  - The document is not submitted to PLOOI
  - The publication is blocked with status "exempt"
  - An entry is recorded for IOBJ annual reporting

#### Scenario: Temporary exemption with expiry
- GIVEN an exemption is based on a temporary security concern
- WHEN the legal team creates the exemption
- THEN they can set `expiresAt: "2027-05-15"`
- AND after the expiry date, the system alerts: "Exemption expired; review for publication"

#### Scenario: Exemption review and signature
- GIVEN an exemption is created
- WHEN it awaits approval
- THEN it must be reviewed and digitally signed by a manager/bestuurder
- AND the signature is stored for audit trail compliance

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-022 | Exemption (uitzonderingsgrond) workflow with belangenafweging text per WOO art. 5.1/5.2 | MUST | Proposed |

---

### REQ-WPP-23: Partial Publication (Page-Level Exemption) (Priority: Must)

A document can be published with specific pages or sections exempted (e.g., page 3 redacted, rest public).

#### Scenario: Exemption of specific pages
- GIVEN a 5-page document with trade secrets on page 3
- WHEN an exemption is declared
- AND exemptionScope is set to `"partial-page"`
- THEN:
  - Pages 1–2, 4–5 are published to PLOOI
  - Page 3 is withheld (not included in the PDF/A-2)
  - The DIWOO-XML notes: "Pages withheld per WOO art. 5.1.c"
  - Citizens can see: "This publication has 4 of 5 pages (1 page withheld)"

#### Scenario: Paragraph-level redaction within a page
- GIVEN a page has 3 paragraphs, only the 2nd containing sensitive data
- WHEN redaction is applied with `exemptionScope: "partial-paragraph"`
- THEN:
  - Paragraphs 1 and 3 remain readable
  - Paragraph 2 is blacked out (visible redaction)
  - The text-layer for paragraph 2 is scrubbed (no copy-paste)

#### Scenario: Redaction vs. exemption distinction
- GIVEN two documents:
  - Document A: PII detected, redacted (visible black boxes), rest published
  - Document B: Trade secret exempted (page excluded), rest published
- WHEN citizens view them
- THEN:
  - Document A shows: "Published with redactions" + redacted marks
  - Document B shows: "Published (partial - page(s) withheld per exemption)"

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-023 | Partial publication: a document can be published with specific pages exempted | MUST | Proposed |

---

### REQ-WPP-24: DIWOO Category-Specific Metadata Enforcement (Priority: Must)

Each WOO category requires specific DIWOO metadata fields; the system enforces these before PLOOI submission.

#### Scenario: Raadsstukken metadata requirements
- GIVEN a publication with `wooCategory = "woo-cat-05"` (Raadsstukken)
- WHEN the DIWOO-XML is generated
- THEN the system checks:
  - `dct:title` (required) — raadsstuk title
  - `dcterms:issued` (required) — publication date
  - `dcat:eventDate` (required) — meeting date
  - `dcat:agendaItem` (required) — agenda reference
  - `dcatap:distribution.dcat:mediaType` (required) — application/pdf
- AND if any field is missing, a validation error prevents submission

#### Scenario: Jaarplannen metadata requirements
- GIVEN a publication with `wooCategory = "woo-cat-04"` (Jaarplannen)
- WHEN validation runs
- THEN required fields are:
  - `dct:title` (required)
  - `dcterms:issued` (required)
  - `dcat:planCoverage` (required) — which organisational unit
  - `dcat:period` (optional) — fiscal year

#### Scenario: Metadata autofill from document
- GIVEN a document already has metadata (title, date, zaaktype)
- WHEN a WooPublication is created
- THEN DIWOO fields are auto-populated where possible:
  - Document title → `dct:title`
  - Document creation date → `dcterms:issued`
  - Zaaktype → `dcat:type`
- AND the coordinator can manually correct or add missing fields

#### Scenario: DIWOO validation feedback
- GIVEN DIWOO-XML is generated
- WHEN validation finds missing mandatory field
- THEN a clear error appears:
  - **Missing field**: dcat:eventDate
  - **Category**: Raadsstukken (requires this field)
  - **Action**: Enter the meeting date
- AND submission is blocked until corrected

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-024 | DIWOO category-specific metadata enforcement (e.g. raadsstukken require vergaderdatum + agendapunt) | MUST | Proposed |

---

### REQ-WPP-25: Republication on Update (Priority: Should)

When a published document is updated (corrected), a new version is published to PLOOI with the version chain visible to citizens.

#### Scenario: Update published document
- GIVEN a document was published on 2026-05-15 (KOOP-2026-45782)
- AND the organisation later discovers a redaction error
- WHEN they correct the document and mark "publish-WOO" again
- THEN:
  - A new WooPublication version is created (`documentVersion: 2`)
  - Full anonymisation check is re-run
  - New redaction is applied
  - New PDF/A-2 + DIWOO-XML are generated

#### Scenario: PLOOI receives updated version
- GIVEN the updated publication is submitted to PLOOI
- WHEN PLOOI accepts it
- THEN:
  - A new `koopReference` is assigned (e.g., KOOP-2026-45783)
  - The original KOOP-2026-45782 is marked "superseded"
  - Links between versions are stored (version chain)

#### Scenario: Version chain visibility
- GIVEN a citizen views the publication on open.overheid.nl
- WHEN they view version history
- THEN they can see:
  - **Version 1**: Published 2026-05-15 (superseded)
  - **Version 2**: Published 2026-06-01 (current)
  - Links to download both versions

#### Scenario: Update with kategory change
- GIVEN a published document is reclassified to a different WOO category
- WHEN the new version is published
- THEN the old category and new category are both recorded
- AND the audit log explains the reclassification reason

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-025 | Republication on update with version chain visible to citizens | SHOULD | Proposed |

---

### REQ-WPP-26: Integration with opencatalogi/woo-compliance (Priority: Must)

Published WooPublication records appear in the organisation's local OpenCatalogi instance, feeding the DIWOO sitemap that citizens see alongside PLOOI.

#### Scenario: Publication synced to opencatalogi
- GIVEN a WooPublication transitions to "live"
- WHEN the sync job runs
- THEN:
  - A catalogue record is created in the organisation's OpenCatalogi
  - The record mirrors the WooPublication data (title, category, date, etc.)
  - Links to both the local copy and the PLOOI URL are included

#### Scenario: DIWOO sitemap generation
- GIVEN multiple WooPublication records exist in OpenCatalogi
- WHEN the woo-compliance app generates the sitemap
- THEN:
  - An XML sitemap is created with entries for each publication
  - Each entry includes the PLOOI URL and metadata
  - The sitemap is published at `/.well-known/diwoo-sitemap.xml`

#### Scenario: Organisation's own DIWOO portal
- GIVEN a citizen visits the organisation's local catalog
- WHEN they browse WOO publications
- THEN they see:
  - All published documents (sourced from WooPublication → OpenCatalogi)
  - Filtered by category, date, status
  - Links to the official PLOOI page (open.overheid.nl)

#### Scenario: Robots.txt directives
- GIVEN the organisation publishes WOO documents
- WHEN robots.txt is generated by woo-compliance
- THEN it includes directives to aid harvesting:
  - Allow search engine indexing of DIWOO sitemap
  - Canonical links to avoid duplication

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-026 | Integration with opencatalogi/woo-compliance so the org's own DIWOO sitemap reflects the published items | MUST | Proposed |

---

### REQ-WPP-27: AVG-vs-WOO Weighing Assist (Priority: Should)

When a publication is challenged on GDPR grounds (recht-op-vergetelheid), the system flags affected publications and assists the legal team with the weighing test.

#### Scenario: Right-to-be-forgotten request
- GIVEN a citizen submits a GDPR erasure request for their personal data
- AND the data appears in published WOO documents
- WHEN the request is recorded in the system
- THEN:
  - All affected WooPublication records are automatically flagged
  - The legal team is alerted: "[N] publications affected by AVG request"
  - Each publication shows: "Contains personal data of [name]; erasure request pending"

#### Scenario: AVG-vs-WOO weighing assist
- GIVEN a publication is flagged for AVG review
- WHEN the legal team opens the weighing assistant
- THEN they see:
  - **AVG right**: Right to erasure (Regulation 2016/679 art. 17)
  - **WOO obligation**: Actieve openbaarmaking until end-of-retention
  - **Weighing factors**: [checklist]
    - Is the data still necessary for the original purpose?
    - Is continued publication in the public interest?
    - Are there less-intrusive alternatives (anonymisation)?
  - **Recommendation**: [guidance based on case law]

#### Scenario: Modification decision
- GIVEN the weighing test is complete
- WHEN the legal team decides to modify the publication
- THEN options are:
  - Withdraw the entire publication
  - Update redaction to remove the personal data
  - Keep published but anonymise the specific data points
- AND the decision is recorded with justification

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-027 | AVG-vs-WOO weighing assist (recht-op-vergetelheid claims flag affected publications) | SHOULD | Proposed |

---

### REQ-WPP-28: Annual WOO Report Export (Priority: Should)

At year-end, the system generates a report for IOBJ (Inspectie Openbaarheid van Bestuur) summarizing publications, latency, and bezwaar outcomes.

#### Scenario: Generate annual report
- GIVEN the year is ending (e.g., 2025-12-31)
- WHEN the report generation job runs
- THEN it aggregates:
  - **Total publications**: 487
  - **By category**:
    - Raadsstukken: 145
    - Jaarplannen: 38
    - Bestuurlijke besluiten: 62
    - ...etc.
  - **Average latency per category**: (in days)
    - Raadsstukken: 3.2 days (target ≤7)
    - Jaarplannen: 8.1 days (target ≤30)
    - ...etc.
  - **Bezwaar outcomes**:
    - Received: 12
    - Ongegrond (not upheld): 9
    - Gegrond (upheld, withdrawn): 3
    - Ingetrokken (withdrawn by requester): 0
  - **Exemptions declared**: 5 (with article references)
  - **Rejections by PLOOI**: 2 (with reasons)

#### Scenario: Report format for IOBJ
- GIVEN the annual report is complete
- WHEN it is exported
- THEN it is available in:
  - PDF format (for official submission)
  - CSV format (for further analysis)
  - JSON format (for machine consumption)
- AND it includes metadata:
  - Organisation name
  - Reporting year
  - Report generation date
  - Signing officer (WOO-coordinator)

#### Scenario: Trend analysis
- GIVEN reports from multiple years (2023, 2024, 2025)
- WHEN trend analysis is performed
- THEN it shows:
  - Publications per year (increasing/stable?)
  - Average latency trend (improving?)
  - Bezwaar rate trend
  - Exemption rate trend

#### Scenario: IOBJ audit trail integrity
- GIVEN the annual report is submitted to IOBJ
- WHEN IOBJ samples publications for audit
- THEN they can:
  - Cross-check report counts against the OpenRegister audit log
  - Verify hashes and tampering via hash-before/hash-after
  - Review bezwaar decisions and timelines
  - Confirm all exemptions had documented belangenafweging

| ID | Requirement | Priority | Status |
|----|----|----------|--------|
| WPP-028 | Annual WOO report export (number of publications per category, average latency, bezwaar count) | SHOULD | Proposed |

---

## Reference Standards

- **Wet Open Overheid (WOO)** — Stb. 2021/499 + Stb. 2022/14, in force 1 May 2022
- **DIWOO metadata standard** — KOOP's XML + JSON-LD profiles
- **PLOOI Aanleverkanaal API** — koop.overheid.nl documentation
- **Algemene wet bestuursrecht (Awb)** — bezwaar procedure, 6-week standard termijn (art. 7:1)
- **PDF/A-2 (ISO 19005-2)** — long-term-preservation PDF
- **AVG (GDPR Regulation 2016/679)** — data protection
- **Wet Bescherming Bedrijfsgeheimen** — trade-secret exemption basis
- **TOOI vocabularies** — KOOP-controlled controlled vocabularies for publisher, theme, location
