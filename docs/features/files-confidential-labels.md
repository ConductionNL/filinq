# Confidentiality labels (files_confidential)

DocuDesk reads existing **TSCP/BAILS confidentiality labels** — as applied by the
[`files_confidential`](https://apps.nextcloud.com/apps/files_confidential) app — and
surfaces them as a **read-only sensitivity signal** during anonymisation appraisal.
The goal is simple: when an organisation has already classified a document, DocuDesk
should show that classification rather than silently re-deciding it.

This is a signal, not a policy engine. A label never blocks, skips, redacts, or
relaxes anything.

## Availability guard

`ConfidentialityLabelService` is fully optional:

- If `files_confidential` is **not installed**, `getLabelForFile()` returns `null`
  and nothing is surfaced.
- Labels are read through Nextcloud's public system-tag API
  (`ISystemTagObjectMapper` / `ISystemTagManager`), so DocuDesk takes no hard
  dependency on the app's internals.
- Any tag-API failure is caught and treated as "no label" (debug-logged). Failing
  open is correct here because the label only adds prominence — it can never relax
  a control.

## Vocabulary

Tag names are matched against an admin-configurable name → level map,
`docudesk.confidentiality.label_vocabulary`, seeded with:

| Tag name       | Level |
|----------------|-------|
| Public         | 0     |
| Internal       | 1     |
| Confidential   | 2     |
| Secret         | 3     |

Tags outside the vocabulary are ignored. When a file carries several matching
tags, the **highest level wins**.

## Where it shows up

`AnonymizationService::extractAndDetectEntities()` adds `confidentialityLabel` and
`confidentialityLevel` to its result **only when a label resolves** — the fields are
absent otherwise. This is a transient report field; no OpenRegister schema changes
and nothing is persisted. The entity-review context renders a read-only chip beside
the existing risk chip, using NL Design tokens, hidden when there is no label.

## Optional priority hint

`docudesk.confidentiality.prioritise_analysis` (**default: off**) makes batch and
folder analysis use the normalised level as a *secondary, tie-breaking* sort key
(unlabelled = level 0). With the flag off, ordering is byte-for-byte identical to
before. It only reorders work — it never skips or blocks a document.

## Tests

- `tests/unit/Service/ConfidentialityLabelServiceTest.php` — label + level for a
  tagged file, null when untagged, null when `files_confidential` is absent, null on
  a tag-API exception, highest-wins on multiple matches.
- `tests/unit/Service/AnonymizationServiceConfidentialityTest.php` — result fields
  appear only when a label resolves, and the priority hint is additive (ordering
  unchanged while the flag is off).

> Screenshot of the confidentiality chip is pending a dev instance with
> `files_confidential` installed (ADR-010).
