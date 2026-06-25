# Proposal: docudesk-consent-to-or-gdpr

## Why

Wave 2 of the fleet GDPR consolidation asked each app that carries an app-local
data-subject-rights stack to **adopt OpenRegister's canonical GDPR capability**
(`OCA\OpenRegister\Service\Gdpr\DataSubjectRequestService` + `DataSubjectDeadline`,
exposed at `/api/gdpr/*`) instead of re-implementing it — mirroring pipelinq's
`pipelinq-avg-adopt-or-gdpr` change, which introduced an `OrGdprBridge` and
re-pointed its AVG (art-15/16/17/18/20) data-subject-rights legs at OR.

The hypothesis going in was that docudesk's `ConsentService` family duplicated
OR's data-subject-rights mechanics (deadline calculation, subject-data discovery,
erasure / rectification / restriction / objection-to-processing) and could be
re-pointed at OR the same way.

## What Changes

This is an assessment-and-decision change (kind: code). No app code is migrated.
The substantive change is the recorded boundary decision plus one safe partial
(the `test:l10n` fix). Findings below.

## What we found (assessment)

docudesk's consent stack is **not** a GDPR data-subject-rights stack. It is a
**publication-disclosure clearance** workflow grounded in the Dutch
**Wet open overheid (WOO)** active-disclosure regime:

| docudesk surface | What it actually is | OR equivalent? |
|---|---|---|
| `ConsentService` / `ConsentCrudService` / `ConsentController` (`/api/consents/*`) | Per-document, per-detected-entity **publication consent** records + standing-consent (scope=entity) policy + prohibition rules | None — OR owns *data-subject requests*, not *publication clearance* |
| `ObjectionDeadlineChecker` | The **WOO publication objection window** (default **28 days**, configurable via `publication_objection_period_days`) during which a third party may object to a document being **published** | `DataSubjectDeadline` is the **EU GDPR art-12(3)** *request-response* term (fixed **1 month** + one **2-month** extension) — a different period, on a different legal basis, with no configurable WOO period |
| `ConsentUpdateHandler` / `ConsentScopeValidator` / `ConsentNotesHelper` / `PolicyMatchService` | Consent-status lifecycle, standing-consent admin RBAC, sentinel-tagged legal-basis notes, prohibition/standing-consent rule matching | None |
| NER anonymisation (`AnonymizationService`, `BatchAnonymizeService`, `AnonymiserBackendStateClient`) | Document PII detection / redaction pipeline | KEEP (explicitly out of scope) |

Crucially, docudesk has **no data-subject-rights surface at all**: a repository-wide
scan found **zero** occurrences of subject-data discovery, access export, erasure,
rectification, restriction, or objection-to-processing. The only "objection" in the
codebase is the WOO **publication** objection window. There is therefore no
`findSubjectData` / `assembleAccessExport` / `rectify` / `erase` / `setRestriction` /
`setObjection` leg to re-point — unlike pipelinq, whose AVG-verzoek workflow genuinely
implemented those rights.

## Decision: NO migration — safe partial only

The pipelinq adoption was a *behaviour-preserving floor improvement* on a workflow
that was already an art-12(3) data-subject-request workflow. docudesk is a different
legal domain. The only delegation candidate — substituting OR's `DataSubjectDeadline`
(1 month / +2 months, GDPR art-12(3)) for docudesk's `ObjectionDeadlineChecker`
(configurable 28-day WOO objection window) — would **change a legal control**: it would
silently alter the length and statutory basis of the objection period, and OR has no
notion of a configurable WOO period. Per the Wave-2 stop rule ("if clean
behaviour-preserving delegation isn't possible without weakening a legal control, STOP
and report the boundary precisely"), no `OrGdprBridge` is introduced and no consent leg
is re-pointed.

This change records that boundary as the canonical decision so a future wave does not
re-attempt the same migration, and ships the one **safe partial** that does belong here:
the pre-existing `test:l10n` failure (missing English source strings, including the
`StandingConsentIndex.vue` consent-officer UI strings such as "Written" / "Yes" /
"Verbal" / "Valid Until") fixed via docudesk's own l10n extraction (`test:l10n:write`,
key === English source).

## Capabilities

### Modified Capabilities

- **publication-consent**: records the WOO publication-consent / objection-period
  boundary against OR's GDPR data-subject-rights capability, and the requirement that
  the WOO objection period MUST NOT be delegated to OR's art-12(3) deadline helper.
