# Design: E-Discovery and Legal Hold

## Context

DocuDesk's retention engine is designed for routine records management: documents age out and are deleted or archived per policy. But when a matter (litigation, regulatory investigation, Woo-verzoek, AVG access request, internal investigation) is active, retention MUST pause. Without a hold mechanism, organizations must either disable retention globally (storage bloat, AVG non-compliance) or risk spoliation claims and EU fines.

The EDRM (Electronic Discovery Reference Model) phase model provides the canonical workflow:
1. **Identify/Preserve** — Matter is opened; hold scope is defined (date range, custodians, registers, keywords).
2. **Collect** — Documents matching scope are collected into the matter view.
3. **Process** — Documents are reviewed; privileged, responsive, and irrelevant documents are tagged.
4. **Review** — Reviewers examine tagged documents; conflict-check, redact privileged passages.
5. **Produce** — Responsive, non-privileged, redacted documents are exported to the requesting party or published.
6. **Presentation** — Documents are presented in court, to regulators, or published.

DocuDesk already has the corpus (documents in OpenRegister), retention rules, and redaction-at-scale capability. This change adds the preserve → review → produce workflow on top.

## Goals / Non-Goals

**Goals:**
- A first-class **Matter** object that owns a legal proceeding or access request (litigation, regulatory, Woo, AVG, internal investigation).
- A **Hold** that preserves documents by scope (custodians, date range, registers, keywords) and suspends retention evaluation.
- A **Custodian** workflow that names individuals whose data is in scope, delivers a hold notice, requires acknowledgement, sends reminders, and escalates to manager.
- A **ReviewTag** surface so reviewers can tag documents (responsive, not_responsive, privileged, hot, needs_redaction, confidential) with full audit trail.
- A **ProductionSet** that bundles finalized responsive documents, encrypts them with a passphrase, generates a load file (bates numbers, paths, hashes, tags), and tracks delivery.
- An immutable **AccessAudit** trail for every document interaction (viewed, downloaded, tagged, redacted, exported).
- Retention engine integration: hold suspension (when doc is under active hold, skip deletion/archival).
- Custodian notice integration: in-app + email notification, acknowledgement tracking, configurable reminders, escalation.
- Search performance: query 100k-document matters in ≤3 seconds.

**Non-Goals:**
- E-discovery platform integration (Relativity, Everlaw) — deferred to openconnector follow-up.
- Advanced redaction UI (mark-up tool, OCR-based PII detection) — deferred; redaction-at-scale pipeline reuse.
- Multi-language UI for custodian notices — v1 Dutch only.
- Holds that automatically update scope based on new documents arriving — static scope in v1; refresh workflow as follow-up.
- Litigation hold accounting (budget tracking, cost center allocation) — deferred.
- Clawback (retract a produced set if error is discovered) — deferred; versioning in production sets as follow-up.

## Decisions

### D1: Matter is the root entity; Hold, Custodian, ReviewTag, ProductionSet all reference Matter

A **Matter** (matterNumber, title, status, dueDate, etc.) is the root. A Hold suspends retention for a Matter's scope. Custodians are named for a Matter. ReviewTags are applied to documents in a Matter. ProductionSets bundle the final output.

**Rationale:** The matter is the unit of legal work; everything else (holds, reviews, exports) is a phase within that matter. Bundling them prevents orphaned holds or reviews that have no legal context.

**Alternative considered:** Hold as root, Matter as optional metadata. Rejected: a hold without a matter is meaningless — it has no legal purpose, no dueDate, no one responsible.

### D2: Hold scope is structural (custodians, date range, registers, keywords, tags) not just keywords

The `scopeFilter` on Hold is **not** a free-text search query. It is a structured object:
```json
{
  "dateRange": { "from": "2024-01-01", "to": "2024-12-31" },
  "custodians": ["uuid1", "uuid2"],
  "registers": ["document", "email"],
  "schemas": ["Document", "Email"],
  "keywords": ["contract", "settlement"],
  "tags": ["priority"]
}
```

All fields are AND-combined (AND-semantics within each field array). Documents matching the scope are materialized into the Matter once hold is activated.

**Rationale:** Retention evaluation happens per-document, not per-query. A hold scope must be precise and stable so we know exactly which documents are under hold. Keyword queries are too loose and change meaning if a document is re-indexed.

**Alternative considered:** Free-text search query. Rejected: can't trust search engines to consistently evaluate the same query over time (reindex, algorithm change, corpus change).

### D3: HoldNotice models the custodian acknowledgement workflow

A **HoldNotice** object tracks delivery, acknowledgement, reminders, and escalation **per custodian per hold**:
- `deliveredAt`: when the notice was first sent.
- `acknowledgedAt`: when the custodian clicked "I acknowledge" in the UI.
- `acknowledgementText`: optional free-text response from the custodian.
- `reminderCount`: how many reminders have been sent (0, 1, 2, …).
- `escalatedTo`: the custodian's manager (if reminders exceeded threshold).

This replaces the "send email and hope" pattern with a trackable, escalatable workflow.

**Rationale:** Litigation requires proof that custodians were notified. HoldNotice is the audit trail.

**Trade-off:** Custodians must have Nextcloud accounts and log in to acknowledge. For external custodians (e.g., former employee, third party), a follow-up change can add a one-time token + email link flow.

### D4: ReviewTag is per-document per-matter, with full audit trail

A **ReviewTag** (`documentId`, `matterId`, `tag`, `taggedBy`, `taggedAt`, `notes`) is applied by a reviewer to mark a document's status within a matter.

**Rationale:** The same document may be responsive to one matter and privileged in another. Coupling tags to `matterId` allows the same document to be reviewed in parallel across multiple matters.

**Alternative considered:** Tag on the document object itself. Rejected: cross-matter confusion; no scoping.

### D5: ProductionSet includes encrypted ZIP, load file, and passphrase protection

A **ProductionSet** bundles finalized responsive documents:
- `documentIds[]`: the set of documents included (post-privilege filter, post-redaction).
- `format`: pdf_bundle, native_with_loadfile, encrypted_zip.
- `passphrase`: hashed; decryption key delivered separately.
- Load file: CSV or XML with columns [bates_number, original_path, md5_hash, responsiveness_tag].

**Rationale:** EDRM standard. Load file enables opposing counsel to correlate their copies with ours (by hash). Encryption + passphrase protects attorney-client privilege in transit.

**Alternative considered:** Produce as OpenRegister relation objects. Rejected: production is a snapshot; documents may be deleted later, but the produced set must remain intact and bit-identical.

### D6: AccessAudit is append-only; no UPDATE/DELETE

Every interaction with a held document is logged: viewed, downloaded, tagged, redacted, exported. The AccessAudit table forbids UPDATE/DELETE (insert-only).

**Rationale:** Regulatory/litigation defense requires an immutable record of who did what when. If a lawyer can delete their own audit trail, the audit trail is useless.

**Implementation:** OpenRegister's `SaveObject` currently enforces immutability via archival status. A follow-up OpenRegister change will add a schema-level `immutable: "append-only"` flag to enforce this without side-effects.

### D7: Retention suspension is a hook, not a separate hold-check table

When the retention engine evaluates a document for deletion/archival, it calls `HoldService::documentUnderActiveLold($documentId)`. If true, skip the action and log. No separate "held documents" table.

**Rationale:** Minimal footprint. The holds themselves are in OpenRegister; we query them on-demand.

**Trade-off:** Every retention evaluation has a query overhead. Mitigation: cache active holds in-memory per app session; index by document hash or date range.

### D8: Custodian is optional; a matter without custodians is valid

A **Custodian** binds a user to a matter and tracks their data sources (which systems they have mailbox, file shares, databases on). But `matterId` → `custodian[]` is optional in v1.

**Rationale:** Some matters are pure-document (e.g., published records); no custodians. Other matters (e.g., employee investigation) need custodian tracking.

### D9: Seed data covers municipality (Woo-verzoek), consultancy (internal investigation), travel agency (customer complaint)

Seed matters demonstrate the three ADR-016 personas: Gemeente Demostad, Conduction B.V., ReisBureau Zonnestraal. Each has a matter, a hold with realistic scope, custodians, and sample review tags.

**Rationale:** Demos, screenshots, and testing start with populated data.

## Risks / Trade-offs

**[Hold scope materialization]** — When a hold is activated, we query the corpus to count matching documents. For a 1M-document corpus, this may take minutes.
→ Mitigation: show a progress bar; run as background job; report count incrementally.

**[Custodian token expiry]** — A HoldNotice token (for external custodian email link) has a TTL. If expired, custodian can't acknowledge.
→ Mitigation: allow re-issuing tokens; send reminder email with fresh token.

**[Redaction blocking]** — A product set can't be exported if any privileged document isn't redacted. This blocks the entire export.
→ Mitigation: allow "export with unredacted exclusions" (defer those docs to next cycle); track backlog in UI.

**[Production-set storage]** — Exported ZIPs are large (100k documents @ 1MB avg = 100GB+). Storage must be provisioned.
→ Mitigation: production sets have their own retention rule (e.g., keep for 7 years, then delete); periodic archival to cold storage.

**[AccessAudit volume]** — Append-only logs grow unbounded. A 100k-document matter with 10 interactions per document = 1M audit rows.
→ Mitigation: index on `matterId` + `documentId` + `action` for fast querying; periodic export to compliance archive (cold storage); retention policy on audit rows (e.g., keep for statute of limitations + 3 years).

## Seed Data

Per ADR-016, seed objects cover the three personas: a Dutch municipality ("Gemeente Demostad"), a consultancy ("Conduction B.V."), and a travel agency ("ReisBureau Zonnestraal").

### Seed Matters

**Matter 1 — Gemeente Demostad (Woo-verzoek)**
- `matterNumber`: "WOO-2025-0342"
- `title`: "Woo-verzoek Transparantie Subsidies Cultuur"
- `matterType`: "woo"
- `description`: "Publieke verzoek van lokale krant om alle subsidietoekenningen cultuur 2022–2024. Deadline 15 juni 2026. Anonimiseren vóór publicatie op overheid.nl."
- `dueDate`: "2026-06-15"
- `leadReviewer`: user uuid for legal@demostad.nl
- `status`: "open"
- `openedAt`: "2026-02-14T09:00:00+00:00"
- `openedBy`: user uuid for legal@demostad.nl
- `jurisdiction`: "Nederland"

**Matter 2 — Gemeente Demostad (Interne Onderzoek)**
- `matterNumber`: "INT-2025-0087"
- `title`: "Onderzoek Mogelijke Belangenverstrengeling Bouwcommissie"
- `matterType`: "internal_investigation"
- `description`: "Onderzoek naar verdachte bouwvergunningen 2024-2025. HR-afdeling in overleg met externe compliance-firma."
- `dueDate`: "2026-08-30"
- `leadReviewer`: user uuid for hr@demostad.nl
- `status`: "in_review"
- `openedAt`: "2025-09-01T10:30:00+00:00"
- `openedBy`: user uuid for hr@demostad.nl

**Matter 3 — Conduction B.V. (AVG Inzage)**
- `matterNumber`: "AVG-2025-0019"
- `title`: "AVG Art. 15 Inzageverzoek Werknemersgegevens"
- `matterType`: "avg_inzage"
- `description`: "Voormalige werknemer verzoekt kopie van alle verwerkte persoonsgegevens per AVG Art. 15. Termijn 30 dagen."
- `dueDate`: "2026-06-03"
- `leadReviewer`: user uuid for privacy@conduction.nl
- `status`: "producing"
- `openedAt`: "2026-05-04T14:00:00+00:00"
- `openedBy`: user uuid for privacy@conduction.nl

**Matter 4 — ReisBureau Zonnestraal (Klachtendossier)**
- `matterNumber`: "KLACHT-2025-1204"
- `title`: "Klachtenanalyse Zomerseizoen 2025"
- `matterType`: "internal_investigation"
- `description`: "Intern onderzoek naar patroon in klachten zomer 2025 (accommodaties, transfers, gidsen). Deels gedeeld met verzekeraar en branchevereniging."
- `dueDate`: "2026-03-31"
- `leadReviewer`: user uuid for complaints@zonnestraal.nl
- `status`: "in_review"
- `openedAt`: "2025-09-15T11:00:00+00:00"
- `openedBy`: user uuid for complaints@zonnestraal.nl

### Seed Holds

**Hold 1 — Woo-verzoek subsidies (Matter WOO-2025-0342)**
- `matterId`: uuid of Matter 1
- `scopeDescription`: "Alle beslissingen en correspondentie betreffende subsidietoekenningen cultuur 2022–2024"
- `scopeFilter`:
  - `dateRange`: { "from": "2022-01-01", "to": "2024-12-31" }
  - `custodians`: [uuids of Finance Director, Culture Officer]
  - `registers`: ["document"]
  - `schemas`: ["Document"]
  - `keywords`: ["subsidie", "cultuur", "toekenning"]
  - `tags`: []
- `issuedAt`: "2026-02-14T10:00:00+00:00"
- `issuedBy`: user uuid for legal@demostad.nl
- `status`: "active"
- `releasedAt`: null
- `releasedBy`: null
- `releaseReason`: null

**Hold 2 — Interne onderzoek bouwcommissie (Matter INT-2025-0087)**
- `matterId`: uuid of Matter 2
- `scopeDescription`: "Alle emails, documenten en verslagstukken van bouwcommissieleden september 2024–december 2025"
- `scopeFilter`:
  - `dateRange`: { "from": "2024-09-01", "to": "2025-12-31" }
  - `custodians`: [uuids of 5 Commission Members]
  - `registers`: ["document", "email"] (if email register exists)
  - `schemas`: ["Document", "Email"]
  - `keywords`: ["bouwvergunning", "commissie", "belangenverstrengeling"]
  - `tags`: []
- `issuedAt`: "2025-09-01T11:00:00+00:00"
- `issuedBy`: user uuid for hr@demostad.nl
- `status`: "active"
- `releasedAt`: null
- `releasedBy`: null
- `releaseReason`: null

### Seed Custodians

**Custodian 1 — Gemeente Demostad, Finance Director (Matter WOO-2025-0342)**
- `matterId`: uuid of Matter 1
- `user`: uuid of Gemeente Finance Director
- `role`: "employee"
- `startedAt`: "2026-02-14T10:00:00+00:00"
- `endedAt`: null
- `dataSources`: ["email", "file-share", "finance-system"]

**Custodian 2 — Gemeente Demostad, Culture Officer (Matter WOO-2025-0342)**
- `matterId`: uuid of Matter 1
- `user`: uuid of Gemeente Culture Officer
- `role`: "employee"
- `startedAt`: "2026-02-14T10:00:00+00:00"
- `endedAt`: null
- `dataSources`: ["email", "file-share"]

(Similar custodians for other matters…)

### Seed Hold Notices

**HoldNotice 1 — Finance Director receives hold notice (Hold 1, Custodian 1)**
- `holdId`: uuid of Hold 1
- `custodianUser`: uuid of Finance Director
- `deliveredAt`: "2026-02-14T10:15:00+00:00"
- `acknowledgedAt`: "2026-02-14T11:30:00+00:00"
- `acknowledgementText`: "Bevestigd. Alle relevante documenten beveiligd."
- `reminderCount`: 0
- `escalatedTo`: null

**HoldNotice 2 — Culture Officer receives hold notice (Hold 1, Custodian 2)**
- `holdId`: uuid of Hold 1
- `custodianUser`: uuid of Culture Officer
- `deliveredAt`: "2026-02-14T10:15:00+00:00"
- `acknowledgedAt`: null
- `acknowledgementText`: null
- `reminderCount`: 1 (first reminder sent 2026-02-18)
- `escalatedTo`: uuid of Culture Officer's manager

### Seed Review Tags

**ReviewTag 1 — Responsive subsidie-beschikking**
- `documentId`: uuid of sample Decision Document
- `matterId`: uuid of Matter 1
- `tag`: "responsive"
- `taggedBy`: user uuid of legal-reviewer@demostad.nl
- `taggedAt`: "2026-03-01T14:30:00+00:00"
- `notes`: "Directe subsidie aan gemeente. Moet worden openbaargemaakt."

**ReviewTag 2 — Privileged email between Mayor & Lawyer**
- `documentId`: uuid of sample Email
- `matterId`: uuid of Matter 1
- `tag`: "privileged"
- `taggedBy`: user uuid of legal-reviewer@demostad.nl
- `taggedAt`: "2026-03-05T09:15:00+00:00"
- `notes`: "Juridisch advies van extern advocaat. Uitzondering AVG Art. 5.1 sub b."

## Migration Plan

1. Merge schema additions into `docudesk_register.json`: new registers (matter, hold, custodian, review, production, audit) and seven new schemas.
2. On `composer run install-deps` → `occ app:enable docudesk` (or upgrade), `ConfigurationService::importFromApp()` loads the updated file.
3. OpenRegister's `RegistersLoader` creates the new registers, installs the seven schemas, and upserts the seed objects (matters, holds, custodians, notices, tags).
4. Existing installs: loader is idempotent on `uuid` and `slug`; re-running on upgrade adds new registers without disturbing existing data.
5. Rollback: remove registers and schemas by reverting `docudesk_register.json` and re-running importer.

## Deduplication Check

**Existing OpenRegister Services Leveraged:**
- `ObjectService` — CRUD for all matter/hold/custodian/review/production/audit objects.
- `AuditTrailService` — tracks who updated what when (reviewer, hold release, notice acknowledgement).
- `NotificationService` — sends hold-notice in-app notifications.
- `FileService` — uploads production-set ZIPs.
- `ArchivalService` — interfaces with retention engine.
- `SearchService` / `IndexService` — full-text search for document review queries.

**No Overlap Found:** The e-discovery workflow (legal hold, custodian notice, production export) is domain-specific and not covered by existing specs. OpenRegister provides the data plumbing; DocuDesk builds the legal workflow on top.

## Open Questions

- **Redaction at scale integration**: How does DocuDesk signal to the redaction pipeline that a document is "privileged" and needs redaction before production? Is this a webhook, a service call, or a background job?
- **Woo publication flow**: Does a Woo production-set auto-flow to opencatalogi, or does legal manually copy the export to the publication system?
- **Multi-matter reviews**: Can a single document be reviewed in parallel across two active matters? (Yes, by design — ReviewTag is per-document-per-matter. But UI support needed.)
- **Custodian token TTL**: For external custodians (non-Nextcloud users), what is the email-link token expiration? Suggest 30 days + renewable.

## Reuse Analysis

All seven schemas (Matter, LegalHold, HoldNotice, Custodian, ReviewTag, ProductionSet, AccessAudit) are new domain objects not covered by existing registers or services. They follow ADR-001 (data layer), ADR-006 (schema standards), and ADR-016 (seed data) exactly. Implementation leverages only platform services (ObjectService, AuditTrailService, NotificationService, etc.) without duplicating any existing capability.
