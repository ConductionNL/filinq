---
kind: code
---

# Proposal: reversible-pseudonymization

## Why

Buyers no longer accept black-box blackout as the only anonymisation mode.
Reversible, readability-preserving pseudonymisation — replace each entity with
a stable, human-readable placeholder, keep an encrypted mapping, restore it
under authorisation — has become the default pattern the market converges on:

- **Microsoft Presidio "PII Shield"** (Mar-2026) is a *reversible* privacy
  proxy — pseudonymise on the way to an LLM, restore on the way back (R2 B, C6).
- **xxllnc Anonimiseren** replaces PII with neutral terms **preserving
  readability** rather than blacking it out; **anonymize.solutions** ships
  Replace/Mask/Hash/Encrypt as first-class actions; the **Hoeksche Waard**
  source-anonymisation tool works the same way (R2 C6, D row "Reversible /
  pseudonymizing anonymization", demand_score **6** — the single
  highest-demand capability in the wave).
- R2 D marks DocuDesk **PARTIAL** here: "`anonymization` built (redaction-style);
  reversible/pseudonymize not explicit." The demand is proven and the gap is
  named.

The use case is concrete: an operator pseudonymises a Woo dossier to share or
process it, then — with a legal basis and an audit trail — needs to recover who
"Persoon-1" was to answer an objection or a data-subject request. Blackout
cannot do that; a reversible mapping can, and doing it with an **encrypted,
access-gated, audit-logged** mapping is what keeps it AVG-defensible.

Verified at HEAD — this is an EXTENSION, not a rebuild (the brief's
"extend rather than duplicate"):

- OpenRegister's redaction engine **already emits readable, stable, scope-local
  placeholders**. `DocumentProcessingHandler::anonymizeDocument()` builds
  `[<localized-type>: <number>]` (e.g. `[PERSOON: 1]`, `[ADRES: 2]`) via
  `PlaceholderIdTranslator` (per-document or per-dossier numbering) and returns
  the exact emitted map through `getLastPlaceholderMap()` — `global entity id →
  placeholder string` (`lib/Service/File/DocumentProcessingHandler.php`,
  `lib/Service/FileService.php`).
- DocuDesk **already consumes that map**:
  `AnonymizationService::anonymizeDocument()` reads
  `getLastPlaceholderMap()` and threads it into the grondslagen summary
  (Robert's `anonimiseren-bij-de-bron` merge, PR#314). The placeholder half of
  "pseudonymise" already ships and is live.
- DocuDesk **already records the source↔anonymised pairing**:
  `recordAnonymizationLink()` writes an `anonymizationLink` object
  (`sourceFileId`/`anonymizedFileId`, both facetable) — the anchor a reversible
  mapping attaches to.
- What is genuinely **missing**: (1) DocuDesk stores **no** reverse mapping
  (placeholder → original value), so today's placeholder output is
  irreversible; (2) there is **no** restore operation; (3) DocuDesk has **no**
  encrypted/`writeOnly` secret store at all (grep: zero `_render:false` /
  `writeOnly` usage in `lib/` + register).

So this change adds the encrypted mapping store, the authorised restore, and a
"reversible" output mode — reusing OR's existing placeholder emission and the
existing `anonymizationLink`, not re-implementing replacement.

## What Changes

- **Reversible output mode**: `anonymizeDocument` gains a
  `reversible` mode alongside the existing (irreversible) behaviour. In
  reversible mode DocuDesk captures OR's `getLastPlaceholderMap()` plus the
  original entity values and persists an encrypted mapping; the default mode is
  unchanged and stores nothing (the irreversible guarantee is preserved).
- **Encrypted mapping store (`pseudonymMap`)**: a new register object keyed to
  the `anonymizationLink`, holding `placeholder → {originalValue, entityType}`
  pairs. The sensitive payload is stored in a `writeOnly` / `_render:false`
  property (OR's secret-render boundary — the only thing that keeps a secret
  out of read responses) **and** encrypted at rest via Nextcloud `ICrypto`
  before it is written (defence in depth: the map is a concentrated PII copy).
- **Authorised restore operation**: `POST api/pseudonymisation/{link}/restore`
  reverses the placeholders in the anonymised document, producing a restored
  copy. Access is fail-closed group-gated; every restore (and every denied
  attempt) is audit-logged via OR's audit trail — an unlogged re-identification
  must never happen.
- **Mapping lifecycle tied to the link**: the `pseudonymMap` is created,
  updated (on re-anonymise) and deleted in lockstep with its
  `anonymizationLink`, so a deleted anonymisation never leaves an orphaned
  re-identification key.
- **UI**: the anonymise dialog offers "reversible (pseudonymise)" vs
  "irreversible (redact)"; the document detail offers a gated "Restore
  original" action that states the audit-logging up front.

## Capabilities

### New Capabilities

- `reversible-pseudonymization`: a reversible anonymisation output mode that
  reuses OpenRegister's readable placeholder emission, stores an encrypted
  `writeOnly` placeholder→original mapping keyed to the `anonymizationLink`,
  and adds a fail-closed, audit-logged restore operation with a lifecycle tied
  to the link.

### Modified Capabilities

<!-- none re-specced. OR's DocumentProcessingHandler placeholder emission,
     getLastPlaceholderMap, FileService and the existing anonymizationLink
     schema are consumed unchanged. The `anonymization` capability gains a
     mode flag via its existing anonymizeDocument seam — extended through the
     documented parameter, not modified in its canonical spec here. -->

## Impact

- `lib/Settings/docudesk_register.json`: new `pseudonymMap` schema in the
  `document` register — `anonymizationLink` reference, `sourceFileId`,
  a `writeOnly` / `_render:false` encrypted `mappings` payload, `entryCount`,
  `algorithm`; register-i18n on user-facing labels; register version bump with
  changelog. No change to `anonymizationLink`'s existing shape (a `mappingRef`
  pointer is additive).
- `lib/Service/AnonymizationService.php`: capture `getLastPlaceholderMap()` +
  original values in reversible mode and delegate to a new
  `PseudonymMapService` (encrypt + store); wire the map's create/update/delete
  into the existing `recordAnonymizationLink()` lifecycle.
- New `lib/Service/PseudonymMapService.php` (ICrypto encrypt/decrypt, OR
  ObjectService store with `_render:false` payload, org-scoped) and
  `lib/Service/PseudonymRestoreService.php` (reverse placeholders → restored
  copy).
- New `lib/Controller/PseudonymisationController.php` +
  `api/pseudonymisation/{link}/restore` (fail-closed group gate, audit log).
- Admin setting: `docudesk.pseudonymisation.restore_allowed_groups`
  (default `[]` = admins only, fail-closed).
- `src/manifest.json` + views: anonymise-mode choice + gated restore action;
  restore-confirm dialog in its own `src/dialogs/` file.
- Consumes (unchanged, presence-gated): OR `DocumentProcessingHandler`
  placeholder emission + `getLastPlaceholderMap()`, `FileService`,
  `anonymizationLink`, OR audit trail; Nextcloud `ICrypto`.
- Non-overlap (declared dependencies, not re-specced):
  `anonymization-review-workbench` (active; unaffected — mode is chosen at
  anonymise time), `image-redaction` / `redaction-at-scale` /
  `document-sanitization` (active; irreversible-redaction concerns, orthogonal
  to reversible-text pseudonymisation), the `anonymise-*` output-format changes
  (this adds a *reversibility* axis, not an output-format axis). The
  irreversible/redaction path is explicitly left as the default and untouched.
- Evidence: Presidio PII Shield + xxllnc readability-preserving + Hoeksche
  Waard convergence (R2 C6); R2 D reversible-pseudonymization row (demand 6,
  DocuDesk PARTIAL); Robert's `anonimiseren-bij-de-bron` placeholder work
  (PR#314, extended here).
