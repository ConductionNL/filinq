## Why

Prohibitions and standing consents are only enforced in the WOO publication-consent workflow (`ConsentService`, `ConsentDetail.vue`); the generic anonymise/redaction flow is entirely policy-unaware. Two concrete consequences:

1. A `publicationProhibition`-listed entity (court-order witness, undercover officer, minor-protection entry) can be detected and then silently left un-redacted if the operator skips it — the anonymise endpoint never checks.
2. A `standingPublicationConsent`-listed entity is not auto-skipped at analysis, so operators re-decide it on every document.

This change wires both policies into the anonymise flow with a **lightweight, compute-at-guard** design — deliberately NOT the heavier `anonymisation-grondslagen-and-prohibition-gate` approach (per-request re-resolving gate + `acknowledgedOverrides` array + `PolicyOverrideAuditService` + a `prohibitionOverrideAudit` register schema + per-override sequential OR PATCH). That change's **prohibition-gate portion is superseded by this one**; its grondslagen/bases portion is unaffected.

No OpenRegister schema change: `EntityRelation` is an internal OR database entity, so persisting a DocuDesk-policy flag on it would require an OR migration and leak a DocuDesk concept onto a generic table. Instead the matcher runs at analysis (for the standing-consent auto-skip + the frontend hint) and again at the guard (a cheap in-memory pass over the file's entities).

## What Changes

- **NEW:** At analysis time (`extractAndDetectEntities`), every detected entity is matched via the existing `PolicyMatchService` (prohibition wins over standing consent):
  - a **standing-consent** match sets `skip_anonymization = true` on the `EntityRelation` (via OR's `updateDecisionMetadata`) — auto-skip; the operator may still override to anonymise;
  - a **prohibition** match attaches a read-only `prohibitionMatch` hint to the extract response (NOT persisted).
- **NEW:** The extract endpoint response gains a per-entity `prohibitionMatch`: `null`, or `{ ruleId, ruleName, highConfidence }` where `highConfidence = confidence >= threshold`.
- **NEW:** A DocuDesk-owned per-relation skip-decision endpoint (`PATCH /apps/docudesk/api/anonymization/relations/{id}`) that the review UI calls on skip-toggle, in place of PATCHing OpenRegister's `/api/entity-relations/{id}` directly. Setting `skipAnonymization = true` on a prohibition-matched relation is guarded per occurrence, at decision time:
  - `confidence >= threshold` → **absolute**: rejected with **HTTP 422**, and `force` does NOT release it;
  - `confidence < threshold` → rejected with **HTTP 422** **unless** the request sets `force`.
  Including an entity (or a non-skip decision such as `bases`) is always allowed. Allowed decisions forward to OR's `updateDecisionMetadata`. The guard lives in DocuDesk because the prohibition policy is DocuDesk-owned; OR's generic endpoint cannot evaluate it without coupling.
- **NEW:** A `force` parameter (boolean) on the skip endpoint releases only sub-threshold prohibition matches. It never releases a match at or above the threshold.
- **NEW:** A defence-in-depth backstop in the anonymise flow: since OR's PATCH stays open, the anonymise flow re-checks and fails with 422 if an un-redacted relation matches a prohibition at confidence ≥ threshold (absolute tier only).
- **NEW:** The threshold is `docudesk.prohibition.high_confidence_threshold` (app config, default `0.85`), read at request time so runtime changes propagate without restart. The same threshold is used at analysis (`highConfidence`) and at the guard.
- **NEW (frontend):** the review UI locks prohibition-matched entities (high-confidence hard-locked; sub-threshold lockable with a `force` affordance), renders standing-consent entities as pre-skipped, and catches the 422 into an error dialog listing the blocked entities.
- **Read-only w.r.t. the consent register:** the guard consults `publicationProhibition` records only; it MUST NOT create/modify `publicationConsent` records or invoke the publication-clearance workflow.

Accountability: OpenRegister's `EntityRelation` audit-trail already records the actor + timestamp of any `skip_anonymization` flip, so a `force`d sub-threshold skip is auditable without a bespoke DocuDesk audit store.

## Capabilities

### Modified Capabilities

- `anonymization`: the extract response gains per-entity `prohibitionMatch`; standing-consent matches are auto-skipped at analysis; the anonymise endpoint gains the prohibition guard (422 + `force`).

## Impact

- **Code (docudesk):**
  - `lib/Service/PolicyMatchService.php` — `matchProhibition()` (prohibition-only).
  - `lib/Service/AnonymizationService.php` — analysis policy pass (standing-consent auto-skip + `prohibitionMatch` hint on extract); the guarded skip-decision path; the anonymise backstop.
  - `lib/Controller/AnonymizationController.php` (+ batch) — new per-relation skip endpoint accepting `force`; 422 with the structured body; a new route in `appinfo/routes.php`.
  - Frontend: the review store calls the DocuDesk skip endpoint on toggle (not OR's PATCH), plus a new 422 error dialog component.
- **API contract:** new DocuDesk skip endpoint (`PATCH /apps/docudesk/api/anonymization/relations/{id}`, `force` optional, 422 with `prohibitionMatch` + `threshold`); extract response gains `prohibitionMatch` (additive). Callers with no prohibition records see no change.
- **No migration. No OpenRegister change.**
- **Privacy/compliance:** guarantees prohibition-listed entities cannot be silently missed; the absolute tier cannot be `force`d.

## Cross-app Dependencies

- **Soft** — `docudesk:entity-publication-policies` provides the `publicationProhibition` / `standingPublicationConsent` schemas the matcher reads. The guard is a no-op until such records exist.
