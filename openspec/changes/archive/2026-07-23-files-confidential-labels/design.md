# Design: files-confidential-labels

## Context

Verified at HEAD:

- **DocuDesk's optional-dependency idiom.** `MetadataService::getObjectService()`
  (`lib/Service/MetadataService.php` L76–84) guards OpenRegister consumption:
  `if (in_array('openregister', $this->appManager->getInstalledApps(), true))
  then $this->container->get('OCA\OpenRegister\Service\ObjectService') else
  throw`. Callers treat "unavailable" as "skip", not "fail". This change reuses
  the guard shape for `files_confidential`.
- **The attach point.** `AnonymizationService::extractAndDetectEntities(int
  $fileId)` returns `['entities' => $normalized, 'riskLevel' => $riskLevel, …]`
  (`lib/Service/AnonymizationService.php` L269). This result feeds the
  entity-review surface and is the natural place to add the confidentiality
  signal for one file.
- **No priority ordering today.** `grep -n priority` in `FolderBatchService` /
  `BatchAnonymizeService` is empty — batch/folder analysis has no priority
  concept, so the optional priority hint is additive and cannot regress existing
  ordering.
- **`files_confidential` is not installed in this workspace** (verified) — so the
  design binds to Nextcloud's **public** system-tag API (`ISystemTagManager`,
  `ISystemTagObjectMapper`), which is how `files_confidential` surfaces its labels
  (it assigns classification labels as system tags on file nodes). DocuDesk must
  not couple to `files_confidential` internals.

## Goals / Non-Goals

**Goals:**

- Read an existing confidentiality label for a file as a read-only signal, safely
  when `files_confidential` is present and inertly when it is not.
- Surface the label in the document report / entity-review context.
- Optionally let the label suggest (not enforce) batch/folder analysis priority.

**Non-Goals:**

- No classification/detection engine (files_confidential owns labelling; DocuDesk
  owns PII detection — these stay separate).
- No policy/enforcement/gating on the label (no block, no forced redaction, no
  publication veto — those are `entity-publication-policies` / prohibition-gate
  territory, explicitly out of scope).
- No write to `files_confidential` or its tags — read-only.
- No coupling to `files_confidential` PHP internals — read via NC's public tag
  API only.

## Decisions

### D1 — `ConfidentialityLabelService` (availability-guarded read, the unit seam)

New `lib/Service/ConfidentialityLabelService.php`:

```
getLabelForFile(int $fileId): ?ConfidentialityLabel
```

- **Guard**: return `null` immediately if `files_confidential` is not in
  `IAppManager::getInstalledApps()`. DocuDesk never depends on it at boot.
- **Read**: resolve the file's assigned system tags via
  `ISystemTagObjectMapper::getTagIdsForObjects([$fileId], 'files')` →
  `ISystemTagManager::getTagsByIds()`, take visible tag names.
- **Match + normalise**: match tag names against the admin-configured
  `docudesk.confidentiality.label_vocabulary` (a map of label/tag name →
  normalised level on an ordered scale, e.g. `public=0 < internal=1 <
  confidential=2 < secret=3`; default vocabulary seeds the common TSCP/BAILS
  names). Return the highest-level matching label as
  `{ label: string, level: int }`; `null` if no tag matches the vocabulary.
- **Fail-safe**: any exception from the tag API is caught and treated as "no
  label" (`null`) — a signal read must never break anonymisation. (Fail-*open* is
  correct here precisely because the label only *adds* prominence; it never
  relaxes a control, so absence degrades to "no extra signal", not "less
  protection".)

Resolution of the tag services: constructor-injected NC interfaces
(`ISystemTagManager`, `ISystemTagObjectMapper`, `IAppManager`, `IAppConfig`) —
these are core NC, always present, so no lazy container trickery is needed (the
guard is on `files_confidential` presence, not on the tag API).

Unit-testable in isolation: mock the tag mappers + app manager + config; assert
label/level for a tagged file, `null` for an untagged file, `null` when
`files_confidential` absent, `null` on tag-API exception, and highest-wins when
multiple labels match.

### D2 — Surface in the document report + entity-review context

In `AnonymizationService::extractAndDetectEntities()` result assembly (L269),
call `ConfidentialityLabelService::getLabelForFile($fileId)` and, when non-null,
add `confidentialityLabel` (display string) and `confidentialityLevel`
(normalised int) to the returned array. When null, omit both (or set null) — the
review UI simply shows no confidentiality chip.

**Persistence decision**: the entity-review result is served transiently by the
controller (it is recomputed per review load), so **no OR schema change is
required** for the review context. IF a persisted per-file document report OR
object is later shown to store risk/entity summaries, add nullable
`confidentialityLabel`/`confidentialityLevel` properties there with a register
version bump; provisional decision is transient-only (no schema change) to keep
scope minimal. Recorded as an Open Question.

The review UI adds a read-only confidentiality chip next to the existing risk
chip (NL Design tokens, no hardcoded colour). It is informational — it carries no
action.

### D3 — Optional analysis-priority suggestion (off by default)

Admin flag `docudesk.confidentiality.prioritise_analysis` (bool, default
`false`). When on, batch/folder analysis scheduling uses the normalised level as
a **tie-breaking priority hint** so higher-confidentiality files are analysed
sooner. Mechanics:

- The batch enumerator computes each file's level via
  `ConfidentialityLabelService` (guarded; unlabelled → level 0), and orders the
  work queue by level descending as a secondary sort key (primary order
  unchanged).
- It is a *suggestion*: it changes ordering only. It never skips, blocks or
  redacts, and with the flag off (default) ordering is byte-for-byte identical to
  today.

Rationale for opt-in: reordering analysis has operational implications
(throughput expectations); defaulting off keeps the change a pure additive signal
until an admin opts in.

## Data shapes

`ConfidentialityLabel` (value object): `{ label: string, level: int }`.

Result addition (entity-review context):

```json
{ "entities": [...], "riskLevel": "high",
  "confidentialityLabel": "Confidential", "confidentialityLevel": 2 }
```

`docudesk.confidentiality.label_vocabulary` (IAppConfig JSON):

```json
{ "Public": 0, "Internal": 1, "Confidential": 2, "Secret": 3 }
```

## Security Considerations

- Read-only; no writes to files, tags or `files_confidential`.
- The label read honours the caller's context (tags are read for a file the
  caller is already anonymising / reviewing — same file the review flow resolved);
  it exposes only a classification name the organisation itself assigned.
- Fail-open is safe *only because the signal never relaxes a control* — it adds a
  chip and (optionally) reorders a queue. No security decision keys off its
  presence.
- No new route is added by the service itself; it is consumed inside existing
  anonymisation/review paths that already enforce their auth.

## Risks / Trade-offs

- [`files_confidential` may surface labels via a mechanism other than plain
  system tags in some versions] → the service is written against the public tag
  API and vocabulary-matches by name; if a deployment uses a different surface,
  the vocabulary/config is the single adjustment point and absence degrades to
  "no signal" (no crash).
- [Priority reordering could surprise operators] → default off; documented; pure
  secondary sort.
- [Vocabulary drift between the org's labels and DocuDesk's defaults] →
  admin-configurable vocabulary; unmatched tags are simply ignored.

## Migration Plan

Additive. New service + two admin config keys + two optional result fields; no
schema change under the provisional transient-report decision. DocuDesk with
`files_confidential` absent behaves exactly as today. Rollback = remove the
service call in the result assembly and the config keys.

## Open Questions

- Persist the confidentiality signal on a document-report OR object, or keep it
  transient? Provisional: transient (no schema change). If a persisted report
  object gains risk/entity fields, add nullable confidentiality fields there with
  a version bump.
- Default label vocabulary: seed with the common TSCP/BAILS English names
  (Public/Internal/Confidential/Secret) — confirm the exact BAILS label set with
  a deployment that runs `files_confidential`; the config makes this adjustable
  without code change.
