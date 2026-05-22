## Context

Dutch government organisations process thousands of sensitive documents per month under Woo (wet open overheid) and AVG requirements. Today they redact manually in Adobe Acrobat—which is labour-intensive, inconsistent, and leaks data when text remains selectable. The shift from reactive to proactive openbaarmaking has accelerated demand: some Conduction klanten estimate 50–100 hours/month in manual redaction alone.

A redaction pipeline needs to:
1. Detect patterns (BSN via 11-proef, IBAN via MOD-97, phone/email/postcode/kenteken via regex) at high throughput (≥50 pages/min).
2. Run Dutch-language NLP to catch names and organisations that don't match patterns.
3. Let reviewers add/remove/override annotations before export.
4. Produce true-redacted PDFs (text removed from content stream, not visually covered).
5. Maintain a complete audit trail for Woo/AVG compliance.
6. Support bulk processing (folder or matter selection) and role-based access to originals.

## Goals / Non-Goals

**Goals:**
- A job-based pipeline with async processing and progress tracking.
- Pattern library (11-proef BSN, MOD-97 IBAN, regex for phone/email/postcode/kenteken).
- NLP entity recognition (PERSON, ORG, LOC) using a Dutch-trained model.
- Reviewer workflow: display annotations, add/remove/override, approve for export.
- Side-by-side preview (original ↔ redacted) with per-annotation toggle.
- True PDF text removal (stream mutation, flattening, re-extraction verification).
- Bulk mode: queue multiple documents, aggregate progress, summary reports.
- Unredact access: role-gated original retrieval with audit trail and notification.
- Profile versioning: reproducible jobs across profile updates.

**Non-Goals:**
- Custom NLP model training per-tenant (use off-the-shelf Dutch model).
- Advanced redaction UI (multi-user collaborative annotation, conflict resolution) — v1 is single-reviewer.
- Legal-basis selection UI (users will use external legal tools; we surface the redacted output).
- Integration with Adobe or Word redaction plugins (out of scope for v1).

## Decisions

### D1. Job-based pipeline with async processing

Redaction is queued as a `RedactionJob` and processed asynchronously. The job tracks status (queued, running, completed, failed, partially_completed), mode (auto, manual, hybrid), and output document ID.

**Rationale:** Documents can be large (100+ MB PDFs); pattern matching + NLP may take minutes. Blocking the requester is unacceptable. Async allows bulk processing and capacity planning.

**Alternative considered:** Real-time inline redaction on document download. Rejected: doesn't support reviewer approval workflow or history tracking; re-running redaction on every access is wasteful.

### D2. Pattern validators use standard algorithms (11-proef, MOD-97)

Each `RedactionPattern` has an optional `validator` field. BSN uses the Dutch 11-proef checksum; IBAN uses MOD-97. Other patterns (phone, email, postcode, kenteken) rely on regex only.

**Rationale:** 11-proef and MOD-97 are cryptographic; a valid 11-digit string is ~1 in 11 million. Regex alone would produce too many false positives. Standard algorithms are auditeable and reproducible across implementations.

**Trade-off:** BSN and IBAN validators slow down pattern matching slightly but eliminate 99.99% of false positives.

### D3. NLP entity recognition uses a pre-trained Dutch model

We use a pre-trained Dutch-language NER model (spaCy + NL core or equivalent). Organisations define `entityTypes[]` (PERSON, ORG, LOC, MISC) per profile. Confidence threshold is per-profile.

**Rationale:** Training a custom model per tenant is expensive. Pre-trained models are accurate enough for Dutch (>90% precision on standard corpora) and avoid the operational burden of per-tenant training. Confidence thresholds let users trade recall for precision.

**Alternative considered:** Custom training per tenant or per-document-type. Rejected: complexity, cost, and limited evidence that accuracy improves sufficiently to justify maintenance.

### D4. Annotations are stored separately from the document

`RedactionAnnotation` is a standalone entity (not embedded in `RedactedDocument`). Each annotation references a job, page, coordinates, and optional sourceText (encrypted at rest).

**Rationale:** Allows reviewer override without re-running auto-mask. Allows building a history of annotation changes per job. Decouples the review process from the export process.

**Trade-off:** Requires joining annotations to the job and document; adds a roundtrip to preview.

### D5. True text removal: stream mutation + flattening + verification

The export phase mutates the PDF content stream (removes text objects), flattens all annotations (so they cannot be removed downstream), and verifies the output by re-extracting text and asserting zero matches against source fragments.

**Rationale:** Redaction drawn as a visual rectangle leaves selectable text beneath. Governments have been sued over this (e.g., NJ v. Bridgegate defendants). Mutating the stream is the only reliable way to remove data.

**Alternative considered:** Visual-only redaction (black rectangle over text). Rejected: legal risk and user expectation in Dutch context.

### D6. Unredact access gated by role, logged, and notifies job owner

Users with the `unredact` role can request the original document. The system logs the access in `RedactionAudit` and notifies the job's `requestedBy` user.

**Rationale:** Unredact is a privileged action (accessing data that was intentionally masked). Audit trail and owner notification prevent silent exfiltration.

**Alternative considered:** No unredact; once masked, gone. Rejected: internal users (e.g., legal teams) need to access originals for corrections or appeals.

### D7. Profiles are versioned and shared read-only

A `RedactionProfile` has a `version` field. When a profile is used, the job records the profile UUID + version. Profile sharing is read-only; only the owner can modify a profile.

**Rationale:** Jobs must be reproducible. If a profile is edited after a job runs, the job's history becomes inconsistent. Read-only sharing prevents accidental edits by non-owners.

**Alternative considered:** Profile snapshots (copy on edit). Rejected: adds complexity and conflicts with the shared-read-only model.

### D8. Bulk mode queues per-document jobs, not a monolithic job

When a user starts a bulk redaction on a folder or matter, the system creates one `RedactionJob` per document (not one mega-job for all documents).

**Rationale:** Allows parallelism (multiple workers process documents concurrently). Allows failure isolation (one document's export failure doesn't block others). Simplifies progress tracking (N jobs, N documents processed).

**Trade-off:** Requires aggregation logic to produce bulk summary reports.

## Decisions (continued)

### D9. sourceText is encrypted at rest in RedactionAnnotation

The `sourceText` field in `RedactionAnnotation` (the matched/detected text) is encrypted using DocuDesk's standard encryption context.

**Rationale:** sourceText may contain PII that was *detected but not yet approved for export*. An annotation in "pending" or "rejected" state is sensitive data that must not leak.

**Implementation:** Use DocuDesk's existing `CryptoContext` or `Encryption` service to encrypt/decrypt on store/retrieve.

### D10. Bulk summary includes per-category and per-document aggregates

The bulk summary report lists:
- Total documents processed, failed, partially_completed.
- Aggregate annotation counts by category (bsn, iban, phone, email, postcode, kenteken, person, org, loc, custom).
- Per-document summary (document name, annotation count by category, status).

**Rationale:** Lets organisers understand scope (how much data is being redacted?) and identify outliers (one document with 500 BSN matches suggests corruption).

### D11. Audit trail uses existing OpenRegister audit mapper if available, or separate table

Every action (auto_detected, reviewer_added, reviewer_removed, applied, exported, original_accessed) is logged with actor, timestamp, and job/document/annotation context.

**Rationale:** Woo and AVG require audit trails. Reusing OpenRegister's audit infrastructure avoids duplication; if not available, a separate table is acceptable.

## Seed Data

### Patterns (Standard Library)

Six patterns are seeded:

1. **BSN (Burgerservicenummer)**
   - Category: `bsn`
   - Regex: `\b\d{9}\b` (simplified; real implementation uses 11-proef validator)
   - Validator: `11-proef` algorithm
   - Replacement: `[GEREDACTEERD-BSN]`
   - Severity: `critical`

2. **IBAN**
   - Category: `iban`
   - Regex: `\b[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}\b`
   - Validator: `MOD-97` algorithm
   - Replacement: `[GEREDACTEERD-IBAN]`
   - Severity: `critical`

3. **Telefoonnummer (Dutch)**
   - Category: `phone`
   - Regex: `\b(?:\+31|0)[1-9]\d{1,2}[\s-]?\d{3}[\s-]?\d{4}\b`
   - Validator: none (regex is sufficient)
   - Replacement: `[GEREDACTEERD-TEL]`
   - Severity: `high`

4. **Email**
   - Category: `email`
   - Regex: `\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b`
   - Validator: none
   - Replacement: `[GEREDACTEERD-EMAIL]`
   - Severity: `high`

5. **Postcode (Dutch)**
   - Category: `postcode`
   - Regex: `\b[1-9]\d{3}\s?[A-Z]{2}\b`
   - Validator: none
   - Replacement: `[GEREDACTEERD-POSTCODE]`
   - Severity: `medium`

6. **Kenteken (Dutch license plate)**
   - Category: `kenteken`
   - Regex: `\b[A-Z]{2}-\d{2}-[A-Z]{2}\b|\b[A-Z]{3}-\d{2}-[A-Z]{2}\b` (multiple formats)
   - Validator: none
   - Replacement: `[GEREDACTEERD-KENTEKEN]`
   - Severity: `medium`

### Profiles (Example Configurations)

Three seed profiles demonstrate common use cases:

1. **Profiel: Woo-Publicatie (Default)**
   - Description: "Standaard configuratie voor Woo-besluiten en proactieve openbaarmaking"
   - Language: Dutch (`nl-NL`)
   - Patterns: All six (BSN, IBAN, phone, email, postcode, kenteken)
   - Entity Types: PERSON, ORG
   - Entity Confidence: 0.85
   - Allow List: ["Gemeente Utrecht", "CIBG", "Belastingdienst", "RDW"]
   - Deny List: []
   - Owner: `system` (or first install user)

2. **Profiel: AVG-Inzageverzoek (Data Subject Access Request)**
   - Description: "Andere personen en bedrijfsgegevens verbergen in uitvoering inzaakverzoeken"
   - Language: Dutch
   - Patterns: All six
   - Entity Types: PERSON, ORG
   - Entity Confidence: 0.90 (higher = fewer false positives)
   - Allow List: [requestor's name]
   - Deny List: ["Gemeente Utrecht"] (institutional names always redact)
   - Owner: `system`

3. **Profiel: Juridische Procedure (Legal Hold)**
   - Description: "Maximal redaction: BSN, IBAN, phone, email. No NLP (higher recall, more manual review)"
   - Language: Dutch
   - Patterns: BSN, IBAN, phone, email only (exclude postcode/kenteken for brevity)
   - Entity Types: none (NLP disabled)
   - Entity Confidence: N/A
   - Allow List: []
   - Deny List: []
   - Owner: `system`

### Sample RedactionJob (for testing/demo)

```json
{
  "jobId": "job-woo-2026-q2-001",
  "sourceDocumentId": "doc-gemeente-utk-2024-05-besluit-001",
  "requestedBy": "alice@gemeente-utk.nl",
  "requestedAt": "2026-05-20T09:30:00+02:00",
  "status": "completed",
  "mode": "hybrid",
  "profileId": "profile-woo-publicatie",
  "pageCount": 12,
  "completedAt": "2026-05-20T09:45:00+02:00",
  "outputDocumentId": "doc-gemeente-utk-2024-05-besluit-001-redacted",
  "errorMessage": null,
  "statistics": {
    "annotationsDetected": 23,
    "annotationsApplied": 21,
    "annotationsRejected": 2,
    "categoryCounts": {
      "bsn": 3,
      "email": 8,
      "person": 10,
      "org": 2
    }
  }
}
```

### Sample RedactedDocument

```json
{
  "redactedDocumentId": "rdoc-2026-05-20-001",
  "sourceDocumentId": "doc-gemeente-utk-2024-05-besluit-001",
  "jobId": "job-woo-2026-q2-001",
  "contentHash": "sha256:a3f8e9c2b1d4f7e6a9b8c7d6e5f4a3b2c1d0e9f8a7b6c5d4e3f2a1b0c9d8e7",
  "createdAt": "2026-05-20T09:45:00+02:00",
  "accessibleTo": ["woo_coördinator", "unredact"],
  "retainOriginalUntil": "2027-05-20T23:59:59+02:00"
}
```

### Sample RedactionAnnotation (In-Progress Job)

```json
{
  "jobId": "job-woo-2026-q2-001",
  "page": 3,
  "x": 120,
  "y": 450,
  "width": 180,
  "height": 15,
  "sourceText": "[ENCRYPTED]",
  "category": "bsn",
  "originPattern": "pattern-bsn-11proef",
  "originEntityType": null,
  "addedBy": "system",
  "addedAt": "2026-05-20T09:32:00+02:00",
  "status": "applied",
  "reviewerNotes": null
}
```

## Risks / Trade-offs

**[NLP accuracy varies by document type]** — A legal brief differs from a narrative report. Confidence threshold may need per-document tuning.
→ Mitigation: Make confidence threshold configurable per job (override profile default); capture feedback loop in v2.

**[PDF format variations]** — Some PDFs are text-layer (redaction possible), others are scanned images. Text removal only works on text-layer PDFs.
→ Mitigation: Detect PDF type upfront; fail fast or offer OCR path (deferred to v2); document limitation in UI.

**[Bulk processing can backlog workers]** — Queuing 10,000 documents on one worker tier may take hours; users expect faster feedback.
→ Mitigation: Aggregate progress UI, capacity planning docs, worker auto-scaling config.

**[Pattern false positives]** — Regex patterns will match non-PII (e.g., phone regex matches fictional numbers in descriptions).
→ Mitigation: Make pattern regexes precise; seed with high-precision patterns; allow-list for common false positives.

**[Annotation state drift]** — If a reviewer changes their mind after export, there's no "un-export". Annotations are applied, not reversible.
→ Mitigation: Require export approval (two-person rule) before rendering final PDF; document immutability in UI.
