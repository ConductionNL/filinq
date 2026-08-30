# Design: reversible-pseudonymization

## Context

Verified at HEAD (Filinq `spec/market-gap-wave3-2026-07`, OpenRegister HEAD):

- **OR already emits readable, stable placeholders.**
  `DocumentProcessingHandler::anonymizeDocument()` replaces each entity with
  `[<localized-type>: <number>]` — the type localized to the user's language
  (`PERSON → PERSOON`, `localizeEntityType()`) and the number **scope-local**
  via `PlaceholderIdTranslator` (`perDocument()` by default, per-dossier when
  `scope=dossier`), so the same person is the same number within the scope and
  is never linkable across documents. It records the exact emitted string in
  `lastPlaceholderMap` keyed by the **global entity id**, exposed via
  `FileService::getLastPlaceholderMap()` → `['<entityId>' => '[PERSOON: 1]', …]`.
- **Filinq already consumes that map.**
  `AnonymizationService::anonymizeDocument()` calls `getLastPlaceholderMap()`
  and threads it into `attachGrondslagenSummary()` (Robert's merge, PR#314).
  So the placeholder → readable-token half of pseudonymisation is live; what is
  missing is the placeholder → **original value** reverse map and a restore.
- **The pairing anchor exists.** `recordAnonymizationLink()` writes/updates one
  `anonymizationLink` per source file (`sourceFileId`/`anonymizedFileId`
  facetable, idempotent on `sourceFileId`); re-anonymise updates the same
  record. This is the object a reversible mapping hangs off and shares a
  lifecycle with.
- **Filinq has no secret store today.** Grep of `lib/` + the register found
  **zero** `writeOnly` / `_render:false` usage and no `ICrypto` use. OR's
  render boundary is the documented mechanism: only a `_render:false` property
  is withheld from read responses (fleet reference: writeOnly render boundary,
  or#460/#462). A reversible map is a concentrated PII copy, so it needs both
  that boundary **and** encryption at rest.
- The original entity **values** are available at anonymise time: they arrive in
  the `entities[]` argument to `anonymizeDocument()` (each carries `value`/`text`
  and its `key`/entity id), so pairing a placeholder to its original value needs
  no new detection.

## Goals / Non-Goals

**Goals:**

- A reversible output mode that reuses OR's placeholder emission and stores an
  encrypted placeholder→original mapping keyed to the `anonymizationLink`.
- An authorised, fail-closed, audit-logged restore that reconstructs the
  original from the anonymised copy + the mapping.
- Keep the default (irreversible) path byte-identical and store nothing for it.
- A unit-testable encrypt/decrypt/restore seam provable without a live NC.

**Non-Goals:**

- No new placeholder format or replacement engine — OR owns emission; we read
  its map (extend, not duplicate).
- No change to `anonymizationLink`'s existing fields (only an additive pointer).
- No image/redaction-at-scale work (those are `image-redaction` /
  `redaction-at-scale`, active) — this is reversible **text** pseudonymisation.
- No key-management UI; encryption uses Nextcloud's server secret via `ICrypto`.

## Decisions

### D1 — A `reversible` mode on the existing anonymise seam

`AnonymizationService::anonymizeDocument()` gains a `reversible` flag (default
false = today's behaviour). When true, after OR produces the anonymised file
and returns `getLastPlaceholderMap()`, Filinq assembles the mapping from the
placeholder map (entity id → placeholder) joined with the request `entities[]`
(entity id → original value + type) into
`placeholder → { originalValue, entityType }`, and calls `PseudonymMapService`
to encrypt-and-store it. When false, nothing is stored — the placeholder output
remains irreversible, exactly as now. The mode is orthogonal to `outputFormat`
(pdf/pdf-only/…): reversibility is a *whether-we-keep-the-key* axis, not an
output-format axis.

### D2 — The `pseudonymMap` schema (encrypted, writeOnly)

New schema in the `document` register:

| Property | Type | Notes |
|---|---|---|
| `anonymizationLink` | string (uuid) | reference to the owning link — the lifecycle anchor |
| `sourceFileId` | integer, facetable | mirror for lookup |
| `mappings` | string | **`writeOnly` + `_render:false`** — the ICrypto-encrypted JSON of `placeholder → {originalValue, entityType}`; never returned in a read |
| `algorithm` | string | e.g. `nextcloud-icrypto-v1` (what encrypted it) |
| `entryCount` | integer | number of mapped placeholders (non-sensitive, for the UI) |
| `scope` | string | `document`/`dossier` — mirrors the placeholder-numbering scope |

`mappings` is doubly protected: `_render:false` keeps it out of every
ObjectService read response (so even an authorised object read never leaks it),
and its content is ICrypto-encrypted so a raw DB/backup read is also useless.
The map is only ever decrypted inside `PseudonymRestoreService` on an
authorised restore. Organisation scoping follows the source file's
organisation (fail-closed like the rest of the catalogue).

### D3 — `PseudonymMapService` (store)

`store(int $linkId, int $sourceFileId, array $pairs, string $scope): void`:
serialise `$pairs` to JSON, `ICrypto::encrypt()` it, write a `pseudonymMap`
object via OR ObjectService with the ciphertext in the `_render:false`
`mappings` field and `entryCount = count($pairs)`. Idempotent per
`anonymizationLink` (re-anonymise overwrites the same map). `read()` returns the
decrypted pairs and is **only** callable from the restore path (not exposed on
any read route). Encrypt/decrypt round-trip is the primary phpunit seam.

### D4 — `PseudonymRestoreService` (reverse)

`restore(int $linkId, string $actor): File`:

1. Resolve the `anonymizationLink` → the anonymised file + `pseudonymMap`.
2. Decrypt the mapping (D3).
3. Read the anonymised document's text and reverse each placeholder → original
   value (longest-placeholder-first, mirroring OR's redaction ordering so
   `[PERSOON: 1]` and `[PERSOON: 10]` cannot clobber each other), writing a
   **restored copy** alongside the anonymised file (never overwriting the
   anonymised file — both must remain).
4. Return the restored node.

Restore is a distinct copy, not an in-place edit, so the anonymised artefact is
preserved for audit. Binary formats (PDF) reverse over the text layer where
present; when the anonymised output is a format whose text cannot be safely
rewritten, restore returns a text/JSON re-identification report
(placeholder → original) rather than a corrupted document — honest partial
result, never a silent no-op.

### D5 — Fail-closed authorisation + audit (AVG accountability)

Restore is re-identification — the most sensitive operation in the app:

- Gated by `filinq.pseudonymisation.restore_allowed_groups` (JSON array of NC
  group ids; default `[]` = admins only). Enforced in the controller on the
  restore route (`#[NoAdminRequired]` + explicit in-method gate — semantic-auth
  pattern); a non-member gets 403 with a neutral body; a config read failure
  denies (fail-closed).
- **Every** restore — and every **denied** attempt — is audit-logged via OR's
  audit trail (actor, timestamp, link/source reference, outcome). An unlogged
  re-identification must not be possible; the audit write is on the request
  path and a failed audit write refuses the restore.

### D6 — Lifecycle tied to `anonymizationLink`

The `pseudonymMap` is created/updated when its link is
created/updated (reversible re-anonymise overwrites), and **deleted when the
link is deleted** — so removing an anonymisation removes its re-identification
key, never leaving an orphaned map. `anonymizationLink` gains one additive
`mappingRef` pointer (nullable; absent = irreversible run). No other
`anonymizationLink` field changes.

### D7 — UI

- Anonymise dialog: a mode choice "Reversible (pseudonymise — keeps an
  encrypted key)" vs "Irreversible (redact)" (`NcSelect`/radio with
  `inputLabel`), default irreversible so nothing changes unless chosen.
- Document detail: a gated "Restore original" action, shown only to permitted
  users, whose confirm dialog (own file under `src/dialogs/`) states that the
  restore is audit-logged before it proceeds.

## OpenRegister service usage (ADR-001)

| Operation | Service |
|---|---|
| Placeholder emission + map | OR `DocumentProcessingHandler` / `FileService::getLastPlaceholderMap()` (unchanged) |
| Anonymised/source pairing | OR `anonymizationLink` via existing `recordAnonymizationLink()` |
| Map store/read | OR ObjectService `saveObject()` on `pseudonymMap` (`_render:false` payload) |
| Encryption | Nextcloud `ICrypto` (`encrypt`/`decrypt`) — server secret |
| Restore audit | OR audit trail |

ADR-011 check: encryption uses Nextcloud `ICrypto` (server secret), not a
hand-rolled cipher; no BSN/entity re-validation (values are verbatim from the
detection that already validated them).

## Declarative vs imperative

- **Declarative**: the `pseudonymMap` schema + its `writeOnly`/`_render:false`
  boundary; register-i18n; the additive `mappingRef` on `anonymizationLink`;
  manifest pages.
- **Imperative (justified)**: encryption, the restore reversal, the
  authorisation gate and the audit write (all on the sensitive request path).

## Seed Data

None. A `pseudonymMap` by definition contains real placeholder↔value pairs;
seeding one would fabricate PII. The demo `anonymizationLink` seeds keep
`mappingRef` null (irreversible), and e2e tests create a real reversible run to
exercise the store/restore.

## Security Considerations

- The mapping is the highest-value target in the app: it is `_render:false`
  (never in a read response) **and** ICrypto-encrypted at rest — either alone
  would be insufficient (per the fleet writeOnly-boundary reference).
- Restore is fail-closed group-gated; empty config = admins only.
- Every restore and every denial is audit-logged; a failed audit write refuses
  the restore (no unlogged re-identification).
- Restore writes a distinct copy — the anonymised artefact is never mutated.
- Deleting the anonymisation deletes the key (D6) — no orphaned re-id material.
- No cloud calls; all processing local.

## Risks / Trade-offs

- [Server-secret encryption means a server compromise can decrypt] → accepted
  and documented; `_render:false` + audit narrow the exposure, and per-object
  KMS keys are an OR-side follow-up (Open Questions). Still strictly better than
  today (no reversible store at all) and matches how NC stores other secrets.
- [Restoring a binary PDF's text layer is imperfect] → D4 returns a
  re-identification report instead of a corrupted file when in-place text
  rewrite is unsafe — honest partial result.
- [Reversible mode concentrates PII] → mitigated by making irreversible the
  default, encryption, the render boundary, the access gate and the audit; the
  operator opts in deliberately.

## Migration Plan

Additive: one schema + one additive `anonymizationLink.mappingRef` pointer +
register version bump (boot import), new services/controller/routes/views, one
admin setting, one mode flag defaulting to today's behaviour. No data
migration; existing anonymisations stay irreversible. Rollback = remove the
restore route/UI and the reversible branch; existing `pseudonymMap` objects
remain (encrypted, unreadable without the restore path).

## Open Questions

- **Per-object / KMS encryption keys** instead of the single server secret —
  OR-side follow-up for stronger key separation.
- **Retention of the mapping** (auto-expire the re-identification key after a
  configurable period even while the anonymised file lives) — ties into
  `archiefwet-retention-engine` (active); recorded, not built here.
- **Restore of image/redacted-at-scale outputs** — out of scope; those paths
  are irreversible by nature (`image-redaction` / `redaction-at-scale`).
