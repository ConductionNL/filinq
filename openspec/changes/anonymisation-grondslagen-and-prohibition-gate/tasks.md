## 1. PolicyMatchService availability

- [ ] 1.1 Confirm `PolicyMatchService` exists in `lib/Service/`. If `entity-publication-policies` apply has not yet landed it, scaffold it from that change's spec — at minimum: `matchProhibition(entityText, entityType, resolvedIdentifiers): ?MatchedRule`. Cache active `publicationProhibition` records in memory; subscribe to OpenRegister object-changed events for invalidation.
- [ ] 1.2 If `PolicyMatchService` is being scaffolded here, ensure standing-consent matching (from `entity-publication-policies`) can be added without a refactor — separate the prohibition cache from the standing-consent cache so each side can land independently.
- [ ] 1.3 Unit test `matchProhibition` on its own: high-confidence match, low-confidence match, no match, multi-rule deterministic precedence.

## 2. Anonymise endpoint — bases handling (REMOVED from this change per 2026-05-12 rework)

The original tasks here forwarded `bases[]` from DocuDesk's payload to OpenRegister's anonymise call. **That mechanism is removed** by the post-explore-mode rework. Bases are now set directly on OR's `PATCH /api/entity-relations/{id}` endpoint (or via the `EntityRelationMapper::updateDecisionMetadata` DI method) — by the frontend, batch tooling, or any caller — between the extract and anonymise steps.

DocuDesk's anonymise endpoint payload's `entities[]` shape does NOT gain a `bases` field. Replace the original tasks with:

- [ ] 2.1 Confirm `lib/Controller/AnonymizationController::anonymize` and `lib/Controller/BatchAnonymizationController::batchAnonymize` do NOT add a `bases` field to their accepted payload schema. If any incoming `entities[]` entry includes a stray `bases` field, the controller MUST silently drop it (do not error) — preserves compatibility with any caller still on the old contract.
- [ ] 2.2 Confirm `lib/Service/AnonymizationService::anonymizeDocument` does NOT thread bases through to OR. The OR anonymise call signature is unchanged (`Node $node, array $entities` with entries shaped `{text, entityType, key, ...}`).
- [ ] 2.3 Unit test that a stray `bases` field on an incoming entity entry is silently dropped: payload `entities: [{text, entityType, key, bases: ["x"]}]` succeeds without erroring AND no `bases` key reaches OR's anonymise call.
- [ ] 2.4 Document the bases-set mechanism in the API docs: callers call `PATCH /api/entity-relations/{id}` (or use the DI mapper in PHP-in-process consumers) to set bases. Bases-set is independent of and decoupled from the anonymise call.

## 3. Extract endpoint — prohibitionMatch flag

- [ ] 3.1 Update `lib/Service/AnonymizationService::extractAndDetectEntities` (and the equivalent path used by batch) to call `PolicyMatchService::matchProhibition` for each detected entity and attach a `prohibitionMatch` field to each entity in the response.
- [ ] 3.2 The field is `null` when no match, or `{ruleId, ruleName, highConfidence}` when matched. `ruleName` is the prohibition rule's `primaryName`. `highConfidence` is `entity.score >= threshold`.
- [ ] 3.3 Read the threshold from app config key `docudesk.prohibition.high_confidence_threshold` (default 0.85). Use the existing `IAppConfig` pattern (consistent with how `EntityDetectionService` reads its config).
- [ ] 3.4 Unit test: extract returns `prohibitionMatch: null` when no rules; returns the right object when a rule matches; `highConfidence` reflects the threshold correctly at the boundary.

## 4. Consolidated entities endpoint — prohibitionMatch + suggestedBases

- [ ] 4.1 Update the `GET /api/anonymization/batch/{batchId}/entities` handler to call `PolicyMatchService::matchProhibition` for each consolidated entity. Use `highestConfidence` for the threshold check. Attach `prohibitionMatch` per entity with the same shape as in extract.
- [ ] 4.2 Resolve the dossier(s) that the batch's files belong to. Read each dossier's `bases[]` and union them, deduplicated. Attach `suggestedBases` (array of UUIDs) per entity.
- [ ] 4.3 If files are spread across multiple dossiers, the union is correct (per spec). If files are not in any dossier, `suggestedBases: []` for every entity.
- [ ] 4.4 Unit test the resolver: dossier-bound, orphan, multi-dossier, empty-dossier-bases.

## 5. Prohibition gate at anonymise time

- [ ] 5.1 In `AnonymizationService::anonymizeDocument` (and the batch equivalent), before forwarding the request to OpenRegister, run the gate:
  - resolve all detected entities for the file via the existing `EntityRelationMapper::findEntitiesForFile` path,
  - call `PolicyMatchService::matchProhibition` on each,
  - collect `(ruleId, entityId, confidence, ruleName, entityName)` for each match.
- [ ] 5.2 For each high-confidence match (confidence ≥ threshold), verify the entity is present in the request payload's `entities[]`. If any are missing, fail with HTTP 422 + structured body. Body shape per spec: `{error, missingProhibitionMatches: [{entityId, entityName, ruleId, ruleName, confidence}]}`.
- [ ] 5.3 The `entityName` in the response body MUST come from the OpenRegister `Entity` record's canonical name (NOT the literal detected text, NOT the rule's `primaryName`). Use `EntityMapper::find($entityId)` (or the equivalent OR helper) to resolve the canonical name.
- [ ] 5.4 Application logging: log `ruleId`, `entityId`, `fileId` on a 422 firing. Do NOT log the literal detected text. Canonical entity name MAY appear in logs if it's already in the response payload.

## 6. acknowledgedOverrides — validation, audit, and OR PATCH

- [ ] 6.1 Update the anonymise request payload to accept an optional top-level `acknowledgedOverrides` array. Each entry: `{ruleId, entityId, reason?}`.
- [ ] 6.2 Implement validation: each override MUST correspond to an actual `(ruleId, entityId)` match in the current extraction. Match's confidence MUST be < threshold to release.
- [ ] 6.3 If an override is for a ≥ threshold match: reject with 422 + body containing a `rejectedOverrides` array alongside `missingProhibitionMatches` (the response can have both).
- [ ] 6.4 If an override doesn't correspond to any active match: silently ignore (do not error).
- [ ] 6.5 **NEW (per 2026-05-12 rework):** Add `lib/Service/PolicyOverrideAuditService.php` (or extend an existing audit-like service). For each validated override, write a persistent audit entry capturing `{ruleId, entityRelationId, fileId, reason, acknowledgedBy: <user UID — ADR-005>, acknowledgedAt: <ISO-8601>}`. Smallest implementation: add a `prohibitionOverrideAudit` schema to `docudesk_register.json` and write one object per override; implementations MAY use an alternative persistent store (audit-log table, structured logger payload) if it fits DocuDesk's existing conventions.
- [ ] 6.6 **NEW:** For each validated override, call OR's `EntityRelationMapper::updateDecisionMetadata($entityRelationId, ['skipAnonymization' => true])` via OR's DI lookup (`getOpenRegisterService('OCA\\OpenRegister\\Db\\EntityRelationMapper')`). The DD audit entry MUST be written BEFORE the OR PATCH so a failure of the PATCH doesn't leave the override unrecorded on the DD side.
- [ ] 6.7 **NEW:** Atomic commit semantics — if any one OR PATCH fails (network error, authorization denied), respond with HTTP 500. No further overrides MUST be processed (all-or-nothing per request). DD audit entries written before the failure MAY remain on disk; a retry will idempotently re-PATCH (the skip flag is already-true and that's a semantic no-op on OR's side, no duplicate audit entry).
- [ ] 6.8 Unit test the override validator: valid release, rejected for high-confidence, ignored for non-matching, override array missing/empty.
- [ ] 6.9 Unit test the audit + PATCH side effects: validated override writes one DD audit entry + one OR PATCH; the audit entry contains the right shape; the OR PATCH carries `skipAnonymization: true`; OR's audit-trail (mockable) shows one entry for the skip-flip.
- [ ] 6.10 Unit test failure handling: when OR's `updateDecisionMetadata` raises an exception, DD responds with HTTP 500 and stops processing further overrides.

## 7. In-place wording fix to `entity-publication-policies`

- [ ] 7.1 Edit `openspec/changes/entity-publication-policies/specs/entity-publication-policies/spec.md` — locate the "Out-of-scope behaviors MUST remain unchanged" requirement's "Generic anonymisation flows" bullet. Replace with: *"Generic anonymisation flows (file sanitisation not destined for publication) — these do not invoke `createConsentRequest()` and therefore do not create `publicationConsent` records or pre-empt any workflow. They MAY read the `publicationProhibition` list as a data source for safety checks (e.g. the prohibition gate specced in `anonymisation-prohibition-gate`); read access to a register is not workflow integration."*
- [ ] 7.2 Edit `openspec/changes/entity-publication-policies/specs/consent-management/spec.md` — locate the trigger-boundary preamble paragraph that asserts generic anonymisation does not interact with this policy layer. Apply the same wording tightening: workflow integration vs read-only access are distinguished.
- [ ] 7.3 Run `openspec validate entity-publication-policies` after the edit. Confirm the change still validates clean.

## 8. Unit tests

- [ ] 8.1 `tests/unit/Service/PolicyMatchServiceTest.php` (extend or create) — match types covered, time bounds honoured, prohibition portion of cache populated correctly.
- [ ] 8.2 `tests/unit/Service/AnonymizationServiceTest.php` — gate fires on missing high-confidence; gate passes when all high-confidence are included; gate ignores low-confidence by default; override releases low-confidence; override rejects high-confidence. (Bases-forwarding tests removed; bases are out of this change's surface.)
- [ ] 8.3 `tests/unit/Controller/AnonymizationControllerTest.php` — 422 response shape; `acknowledgedOverrides` accepted on first request; stray `bases` field on payload entry is silently dropped (not rejected, not forwarded).
- [ ] 8.4 `tests/unit/Service/PolicyOverrideAuditServiceTest.php` (NEW) — audit entry shape is correct; one entry per validated override; OR PATCH side-effect order is "DD audit first, OR PATCH second"; OR PATCH failure leaves a 500 response and a DD audit entry on disk.

## 9. Integration tests

- [ ] 9.1 Newman/Postman: extract endpoint returns `prohibitionMatch` per entity (no match, high-confidence match, low-confidence match cases).
- [ ] 9.2 Newman: anonymise gate fires 422 with the documented body shape when a high-confidence prohibition is missing.
- [ ] 9.3 Newman: anonymise succeeds when all high-confidence prohibitions are included in the payload.
- [ ] 9.4 Newman: `acknowledgedOverrides` releases a low-confidence match. Same mechanism rejects an override on a high-confidence match.
- [ ] 9.5 Newman: bases-set lifecycle (replaces the old pass-through test) — PATCH OR's `/api/entity-relations/{id}` with `{bases: ["uuid-a"]}`; then call DocuDesk's anonymise endpoint without any bases on the payload; verify OR's row carries `bases: ["uuid-a"]` after the anonymise call (bases set via PATCH was preserved); verify OR's audit-trail has one entry for the PATCH.
- [ ] 9.6 Newman: override-acknowledge lifecycle (NEW) — submit an anonymise request with `acknowledgedOverrides: [{ruleId, entityId, reason}]`; verify DD-side audit entry exists with the reason; verify OR's relation has `skipAnonymization=true`; verify OR's audit-trail has one entry for the skip-flip; verify the redacted file does not contain that entity's placeholder.

## 10. Documentation

- [ ] 10.1 Update `docs/features/publication-consent-process.md` (or add a new doc `docs/features/anonymisation-prohibition-gate.md`) describing the gate, the override mechanism, the 422 response shape, and the threshold config. Link from the publication-consent-process doc since they share the prohibition concept.
- [ ] 10.2 CHANGELOG entry under "Added": prohibition gate on anonymise endpoint; override-acknowledge audit + skip-flip wiring; `prohibitionMatch` and `suggestedBases` on entity-listing responses. (Bases set via OR's `PATCH /api/entity-relations/{id}` endpoint — documented separately as part of OR's `entity-relation-grondslagen` change.)
- [ ] 10.3 CHANGELOG entry under "Behavior changes": anonymise endpoint may now respond with HTTP 422 when prohibition-listed entities are missing from the request; existing callers that don't have prohibition records configured see no behaviour change.

## 11. Quality and verification

- [ ] 11.1 Run `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan, unit tests) — clean. Fix any pre-existing issues in touched files.
- [ ] 11.2 Manual smoke against a live stack: configure a prohibition rule; extract a doc containing the prohibited entity; verify `prohibitionMatch` in response; submit anonymise without including the entity → 422; submit again with `acknowledgedOverrides` for a low-confidence match → 200 + DD audit entry + OR PATCH → relation has `skipAnonymization=true`. PATCH OR's relation with `bases` set; anonymise; verify the row retains its bases value (set via PATCH, not via anonymise). Requires OR paired change deployed.
- [ ] 11.3 Run `openspec validate anonymisation-grondslagen-and-prohibition-gate` — clean.
- [ ] 11.4 Run `openspec validate entity-publication-policies` after the in-place wording fix — clean.
