status: draft

# Redaction at Scale

## Placement & Information Architecture

**Placement type:** `ACTION` — Action button or menu item on an existing surface. Implemented as a single button / context-menu entry that opens a modal/wizard or runs a backend operation — NOT a page.

**Lives at:** Documenten > Bulk action "Batch redacteren" + detail Redactie tab / Documenten

**Rationale:** Both ad-hoc and batch redaction surface together  
_Source: /tmp/ia-doc-dec-cat-conn.md_

> **Implementation note for builders:** Respect the placement above. Do not promote this spec to a top-level menu item, sub-page, or new route unless the placement type explicitly says so. If the placement is `DETAIL_TAB`, `WIDGET`, `ACTION`, `SETTING`, or `INFRA`, the feature must NOT introduce a new entry in the app sidebar. When in doubt, ask before creating a new top-level surface.

## Purpose

Provide a high-throughput redaction capability inside docudesk so that organisations can mask persoonsgegevens and other sensitive data in documents before publication, sharing, or production. The Dutch context drives most of the demand: every Woo-besluit requires that BSNs, contact details of ambtenaren beneath a certain seniority, third-party personal data, and confidential business information are masked before the document goes online; every AVG-inzageverzoek production must mask other data subjects' details; every dossier shared with a third party may require redaction of irrelevant personal data; and every contract or report shared externally may require masking of pricing, names, or trade secrets.

Today most gemeenten do this by hand in Adobe Acrobat or by drawing black rectangles in Word, which is slow, inconsistent, and leaks data when the underlying text remains selectable. Conduction klanten doing thousands of pages per month under Woo cannot afford manual redaction; the WOB-to-Woo transition combined with proactive openbaarmaking has multiplied the volume.

This spec introduces a redaction pipeline with pattern-based auto-mask (regex for BSN, IBAN, telefoonnummer, postcode, NAW, email, kenteken), NLP entity-recognition for names and organisations (using a Dutch-language NER model), manual override for reviewer-driven additions and exceptions, per-document redaction history so corrections and re-reviews are traceable, side-by-side preview of original versus redacted output, and bulk mode that can process a folder or matter selection in one job. Output is true-redacted (the underlying text is removed from the PDF, not merely covered), with the redaction-history stored separately so internal users with the unredact role can still reach the originals.

## Data Model

**RedactionJob**: jobId, sourceDocumentId, requestedBy, requestedAt, status (queued, running, completed, failed, partially_completed), mode (auto, manual, hybrid), profileId, pageCount, completedAt, outputDocumentId, errorMessage.

**RedactionProfile**: profileId, name, description, patterns[] (FK pattern), entityTypes[] (PERSON, ORG, LOC, ...), allowList[] (terms to never redact), denyList[] (terms to always redact), language, owner, sharedWith[].

**RedactionPattern**: patternId, name, regex, category (bsn, iban, phone, email, postcode, kenteken, creditcard, custom), validator (optional: 11-proef for BSN, MOD-97 for IBAN), replacement, severity.

**RedactionAnnotation**: jobId, page, x, y, width, height, sourceText (encrypted at rest), category, originPattern (FK), originEntityType, addedBy (system or userId), addedAt, status (pending, applied, rejected_by_reviewer), reviewerNotes.

**RedactedDocument**: redactedDocumentId, sourceDocumentId, jobId, contentHash, createdAt, accessibleTo[] (roles), retainOriginalUntil.

**RedactionAudit**: jobId, action (auto_detected, reviewer_added, reviewer_removed, applied, exported, original_accessed), actor, occurredAt.

## Requirements

### REQ-RED-001: Pattern auto-mask
GIVEN a document submitted with an auto-mask profile, WHEN the job runs, THEN every match of every active pattern MUST be detected, validated where a validator is configured (e.g. 11-proef for BSN), and added as a RedactionAnnotation with status "pending" or "applied" depending on the profile setting.

### REQ-RED-002: NLP entity recognition
GIVEN a profile with entity types enabled, WHEN the job runs, THEN the system MUST run a Dutch-language NER model over the document text, produce candidate annotations for PERSON / ORG / LOC entities at the confidence threshold configured in the profile, and MUST suppress candidates that appear in the allowList.

### REQ-RED-003: Manual override
GIVEN a job in reviewer mode, WHEN a reviewer opens the document, THEN the system MUST display all auto-detected annotations with their source category, and MUST allow the reviewer to add new redaction rectangles, remove existing annotations, or change category, with every change captured in RedactionAudit.

### REQ-RED-004: True text removal in export
GIVEN a set of applied annotations on a PDF, WHEN the redacted output is generated, THEN the underlying text MUST be removed from the PDF content stream (not merely covered with a black rectangle), MUST be verified by re-extracting text from the output and asserting zero matches against the source text fragments, and the output MUST be flattened so vector annotations cannot be removed by a downstream viewer.

### REQ-RED-005: Side-by-side preview
GIVEN a job in any state after auto-mask, WHEN a reviewer opens the preview, THEN the system MUST show the original document on the left and the proposed redacted output on the right, with redacted regions highlighted, and MUST allow the reviewer to toggle individual annotations on and off without re-running the auto-mask phase.

### REQ-RED-006: Bulk mode
GIVEN a folder or matter selection containing N documents, WHEN the user starts a bulk job with a chosen profile, THEN the system MUST queue per-document jobs, MUST process at least 50 pages per minute on the standard worker tier, MUST surface aggregate progress, and MUST produce a summary report of detected annotation counts per document and per category.

### REQ-RED-007: Unredact role
GIVEN a redacted document with retainOriginalUntil in the future, WHEN a user with the unredact role requests the original, THEN the system MUST return the original document, MUST log the access in RedactionAudit, and MUST notify the redaction-job owner.

### REQ-RED-008: Profile sharing and governance
GIVEN a RedactionProfile, WHEN the owner shares it with a group, THEN members MAY use it on their own jobs but MUST NOT modify it; profile changes MUST be versioned so prior jobs remain reproducible against the profile version they used.

## Standards

- AVG/GDPR Article 4(5) — pseudonymisation and redaction.
- Woo Article 5.1 (uitzonderingsgronden) — categories that must be redacted before publication.
- BSN-wet — explicit handling rules for burgerservicenummers.
- NIST SP 800-188 — de-identification of personal information.
- PDF 1.7 / ISO 32000 — redaction-annotation syntax compatibility with Acrobat.

## Cross-app

- **docudesk e-discovery-legal-hold**: privileged-passage redaction before production export.
- **opencatalogi**: Woo-besluiten flow through redaction before publication.
- **openconnector**: optional integration with external NER services where local accuracy is insufficient.
- **openregister**: redaction profiles stored as register objects; redacted-document metadata tagged.
- **mydash**: redaction throughput dashboards (pages/week, categories detected, reviewer time saved).

## Target users

Woo-coördinatoren, AVG functionarissen, juridische teams, dossierbeheerders, journalisten in publicatie-redactie, advocatenkantoren bij stukkenproductie.
