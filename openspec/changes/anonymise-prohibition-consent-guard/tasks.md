## 1. Matcher + threshold

- [x] 1.1 Add `PolicyMatchService::matchProhibition(entityText, entityType, resolvedIdentifiers)` returning the prohibition match only (reuse `firstMatchOf`; never returns a standing-consent match).
- [x] 1.2 Read the threshold from app config `docudesk.prohibition.high_confidence_threshold` (default 0.85) at request time. Single reader used by both analysis and the guard.
- [x] 1.3 Unit-test `matchProhibition` (prohibition match; ignores standing consent; no match) and the threshold reader (default + override).

## 2. Analysis policy pass (auto-skip + prohibition hint)

- [x] 2.1 In `AnonymizationService::extractAndDetectEntities`, after entities are detected, match each via `PolicyMatchService::match()` (prohibition precedence).
- [x] 2.2 Standing-consent winner → set `skip_anonymization = true` on the `EntityRelation` via OR `updateDecisionMetadata`. Prohibition winner → do NOT auto-skip.
- [x] 2.3 Attach `prohibitionMatch` (`null` or `{ruleId, ruleName, highConfidence}`) per entity in the extract response. `highConfidence = confidence >= threshold`.
- [x] 2.4 Unit-test: standing-consent entity gets `skip_anonymization=true`; prohibition entity is not skipped; `prohibitionMatch` shape + `highConfidence` boundary.

## 3. Per-relation skip-decision endpoint (primary guard)

- [x] 3.1 Add `PATCH /apps/docudesk/api/anonymization/relations/{id}` (route + `AnonymizationController` method). Accepts `skipAnonymization`, optional `bases`, optional `force`.
- [x] 3.2 When the decision sets `skipAnonymization = true`, resolve the relation's entity and run `PolicyMatchService::matchProhibition`. confidence ≥ threshold → 422 (absolute); confidence < threshold → 422 unless `force`. Including / non-skip decisions are always allowed.
- [x] 3.3 On allow, forward to OR via `EntityRelationMapper::updateDecisionMetadata`. On 422, no OR write; body = `{error, threshold, prohibitionMatch:{entityId, entityName(canonical), ruleId, ruleName, confidence, absolute}}`. Log `ruleId`/`entityId`/relation id only.
- [x] 3.4 Unit-test the outcomes (non-prohibited skip allowed; include always allowed; high-confidence skip → 422 even with force; sub-threshold skip → 422 without force, allowed with force; threshold reclassification) + controller 422 shape.

## 3b. Anonymise backstop (defence-in-depth)

- [x] 3b.1 In the anonymise path, before redaction, re-check the file's un-redacted relations; any prohibition match at confidence ≥ threshold → HTTP 422 (absolute tier only), regardless of `force`.
- [x] 3b.2 Unit-test: a directly-skipped (bypassing the DD endpoint) high-confidence prohibited relation is caught at anonymise.

## 4. Frontend

- [x] 4.1 `EntityReviewTable.vue`: use `prohibitionMatch` to render the skip toggle locked — high-confidence hard-locked ON; sub-threshold lockable with a `force` affordance/warning.
- [x] 4.2 Standing-consent entities render pre-skipped (they arrive with `skip_anonymization=true`).
- [x] 4.3 On skip-toggle, call the DocuDesk skip endpoint (not OR's PATCH) immediately; catch its 422 into an error dialog showing the blocked occurrence (canonical name + rule name), and offer a `force` retry only when `absolute` is false.
- [x] 4.4 Store wiring: route skip decisions through the new endpoint; carry `force`; surface the 422 body.

## 5. Spec bookkeeping + docs

- [x] 5.1 Mark the prohibition-gate portion of `anonimisation-grondslagen-and-prohibition-gate` superseded by this change (note in that change; do not delete its grondslagen/bases portion).
- [x] 5.2 `openspec validate anonymise-prohibition-consent-guard` — clean.
- [x] 5.3 CHANGELOG: prohibition guard (422 + `force`) and standing-consent auto-skip on anonymise; `prohibitionMatch` on the extract response.
- [ ] 5.4 Feature doc note under `docs/features/` (prohibition guard + standing-consent auto-skip, the threshold config, the 422 shape).

## 6. Verification

- [ ] 6.1 `composer check` (PHPCS/PHPMD/Psalm/PHPStan/unit) clean on touched files.
- [ ] 6.2 Manual smoke: configure a prohibition + a standing consent; analyse a doc containing both; standing-consent entity pre-skipped; skip the prohibited entity → 422 in the error dialog; sub-threshold releases with force, high-confidence does not.
