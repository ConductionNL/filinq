## 1. Schema additions in `docudesk_register.json`

- [ ] 1.1 Add the `publicationProhibition` schema definition to `lib/Settings/docudesk_register.json` under the `consent` register's `schemas` block. Properties: `primaryName`, `entityType` (enum PERSON/ORGANIZATION/OTHER), `matchRules` (array of `{type, value}` with `type` enum: exact, normalized, bsn, kvk), `reason`, `legalAuthority`, `caseReference`, `severity` (enum), `jurisdiction`, `addedBy` (user ref), `validFrom`, `validUntil`, `active`, `notes` (markdown). Required: `primaryName`, `entityType`, `matchRules`, `reason`, `active`.
- [ ] 1.2 Extend the existing `publicationConsent` schema with the new universal field `scope` (enum: `document`, `entity`, default `document`). Existing records load with `scope: "document"` automatically.
- [ ] 1.3 Extend the `publicationConsent` schema with the entity-scope-only field set: `matchRules` (same enum as 1.1), `validFrom`, `validUntil`, `active`, `consentMethod` (enum: paper / digital_signature / verbal_recorded / opt_in_form), `consentDocument` (file ref), `consentScope`. None of these fields go in the schema's top-level `required` array — service-level enforcement gates them by `scope`.
- [ ] 1.4 Add the new `policyMatch` property to the `publicationConsent` schema. Use `type: "object"`, `oneOf` with `$ref` to `publicationProhibition` and to `publicationConsent` (the polymorphic-self-reference for standing consents), and `objectConfiguration.handling: "related-object"`. Property is OPTIONAL.
- [ ] 1.5 Confirm the `consentStatus` enum is NOT changed by this work — existing values (`pending`, `consent_given`, `objection_received`, `no_response`, `anonymized`) cover all outcomes; the policy-pre-empted distinction lives in `policyMatch` + `notificationStatus`.
- [ ] 1.6 Verify the schema imports cleanly via `composer test:unit` (or equivalent) — no JSON syntax errors, no schema-validation rejections.

## 2. Seed data per ADR-016

- [ ] 2.1 Add 4 realistic `publicationProhibition` seed objects (per design.md Seed Data section): court order, minor protection, undercover officer, categorical privacy-board exemption. Use the `@self` envelope per ADR-013 with stable slugs.
- [ ] 2.2 Add 4 realistic standing-consent seed objects as `publicationConsent` records with `scope: "entity"`: mayor blanket consent, organization signed opt-in, council member, recorded verbal consent. Use the `@self` envelope. `consentStatus: "consent_given"`, `consentMethod` populated, `validFrom` / `validUntil` set, `active: true`, `legalBasis` populated with the human-readable justification.
- [ ] 2.3 Verify seed import is idempotent — re-running the import skips existing objects matched by slug. Smoke test: install on a clean instance, run the importer twice.

## 3. Detection-time matching service

- [ ] 3.1 Create `PolicyMatchService` (or extend the existing consent service) that loads at service init: all `active: true` `publicationProhibition` records, AND all `active: true` `publicationConsent` records where `scope: "entity"` AND time bounds are open. Index keys: `(matchType, entityType, value)` tuples for O(1) lookup. The service exposes a single method `match(entityText, entityType, resolvedIdentifiers): ?MatchedRule` that returns the highest-priority match or null.
- [ ] 3.2 Implement match-order semantics: prohibitions consulted first, then standing consents. The service returns at most one match. On multi-prohibition match, return the rule with the lowest UUID lexicographically (deterministic).
- [ ] 3.3 Implement time-bound checking at match time: a rule with future `validFrom`, past `validUntil`, or `active: false` MUST NOT match. Applies to both source schemas.
- [ ] 3.4 Implement match types `exact`, `normalized`, `bsn`, `kvk`. `normalized` strips case and accents (use `Transliterator` or equivalent). Reject unknown types in the matcher (defense-in-depth — schema rejects them at write time, but the matcher logs and skips on unknown).
- [ ] 3.5 Subscribe the service to OpenRegister's object-changed events. For `publicationProhibition` events, invalidate and rebuild the prohibition portion of the cache. For `publicationConsent` events, filter by `scope`: `scope: "entity"` events invalidate the standing-consent portion of the cache; `scope: "document"` events do NOT.
- [ ] 3.6 Unit-test the match service: each match type, time bounds, active flag, multi-rule precedence, multi-prohibition tie-break, prohibition-vs-standing-consent conflict. Verify scope-filtering on `publicationConsent` events.

## 4. Consent service integration

- [ ] 4.1 Extend `ConsentService::createConsentRequest()` (or its caller) to consult `PolicyMatchService` before defaulting to the WOO workflow. On prohibition match: create a `scope: "document"` `publicationConsent` with `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `objectionDeadline: null`, `policyMatch` referencing the prohibition UUID. On standing-consent match: create with `consentStatus: "consent_given"`, `notificationStatus: "skipped"`, `publicationDecision: "publish_with_consent"`, `objectionDeadline: null`, `policyMatch` referencing the standing-consent record. On no match: existing WOO behavior unchanged (record gets `scope: "document"`, all workflow fields populated as today, `policyMatch: null`).
- [ ] 4.2 Block status transitions on records whose `policyMatch` is non-null: any attempted transition of `consentStatus` to a different terminal value is rejected. Allowed: re-pointing `policyMatch` if the underlying rule is replaced (still pointing at a permitted referent type), and recording a publication-decision override on standing-consent matches (`publicationDecision: "anonymize"` while `consentStatus` stays `"consent_given"` and `policyMatch` is preserved).
- [ ] 4.3 Implement the `scope` validation at write time in the consent service: reject `scope: "document"` writes missing `documentId`; reject `scope: "entity"` writes that include a `documentId`; reject `scope: "entity"` writes missing `matchRules` or `consentMethod`; reject `policyMatch` on `scope: "entity"` records; reject `publicationConsent` referents in `policyMatch` whose own `scope` is not `"entity"`.
- [ ] 4.4 Unit-test the consent service for the four detection-time outcomes: no match → WOO; prohibition match → anonymized + policyMatch → prohibition; standing-consent match → consent_given + policyMatch → standing consent; both match → anonymized + policyMatch → prohibition (audit log captures both rule UUIDs).
- [ ] 4.5 Unit-test the scope-validation rules at write time (the four corners: scope × valid/invalid required-set).

## 5. Retroactive handling on rule changes

- [ ] 5.1 On `publicationProhibition` rule INSERT (or update making it match more entities — adding a new matchRule, flipping active to true, extending validUntil), the rule-mutation listener MUST find all in-flight `scope: "document"` `publicationConsent` records (status not in {`anonymized`, terminal published states}) for matching entities and force-resolve them: `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `policyMatch` referencing the new rule. Cancel any pending notification dispatch (or no-op if already sent — the entity has the email but the workflow is moot now).
- [ ] 5.2 On standing-consent rule INSERT (a new `publicationConsent` with `scope: "entity"`) or update making it active, do NOT modify in-flight `scope: "document"` records. Future detections benefit; past detections respect what was already decided. Document this asymmetry inline.
- [ ] 5.3 On rule DELETE or expiry (validUntil reached, active flipped to false) — for either source — do NOT modify any past `publicationConsent` record. Past records keep their final state. Future detections fall through.
- [ ] 5.4 Unit-test retroactive force-resolve: in-flight `pending` and `consent_given` records both transition correctly; in-flight `objection_received` records ALSO transition (prohibition overrides explicit objection — privacy default is anonymise, which is what an objection would lead to anyway); `notificationSentAt` and `objectionReceivedAt` are preserved for audit even though `objectionDeadline` is cleared.

## 6. UI — three admin surfaces and toggle behavior

- [ ] 6.1 In the publication-prep screen, render the per-entity anonymization toggle keyed off the type of the referent of `policyMatch`, NOT off `consentStatus` values. For `policyMatch → publicationProhibition`: toggle ON, disabled, with tooltip explaining the entity is on the prohibition list. For `policyMatch → publicationConsent` (scope=entity): toggle OFF (default), enabled, user can flip ON to anonymize anyway. For `policyMatch: null`: existing UX based on `consentStatus`.
- [ ] 6.2 When the user flips the override-up toggle for a standing-consent match (anonymize-anyway), record the override as a status transition — emit an audit event and update `publicationDecision: "anonymize"` while keeping `consentStatus: "consent_given"` and preserving `policyMatch` (the standing consent still matched; only the per-document decision was overridden).
- [ ] 6.3 Use `@conduction/nextcloud-vue` components per ADR-012 (CnFormDialog for any add-rule UI, CnDataTable for list views). No custom equivalents.
- [ ] 6.4 Build three separate admin pages (CnIndexPage views), all routed and linked from the main navigation:
  - **Consent Workflow** — existing per-document consent records list. Add a "policy pre-empted" indicator on rows whose `policyMatch` is non-null. Apply a hard filter: `scope: "document"`.
  - **Standing Publication Consents** — NEW. Filtered view of `publicationConsent` where `scope: "entity"`. List, edit, expire, revoke. The create-form requires `consentMethod` and surfaces a non-blocking warning when `validUntil` is left blank. The form encourages adding stable identifiers (BSN/KvK).
  - **Publication Prohibitions** — NEW. CRUD for `publicationProhibition` records. The create-form encourages adding stable identifiers (BSN/KvK) and warns loudly when only a name-based rule is added.
- [ ] 6.5 Add browser tests (existing Playwright/Jest infra) covering: locked toggle for prohibition matches; defaulted-off-overridable toggle for standing-consent matches; existing flow unchanged for unmatched entities; each admin page lists only the records of its scope/schema; `scope: "entity"` create form requires `consentMethod`; standing consents and prohibitions never appear on the wrong page.

## 7. RBAC configuration

- [ ] 7.1 Configure default RBAC for `publicationProhibition`. Recommended: `read` open to authenticated users in the consent group; `write` restricted to a privileged group (e.g. `docudesk-policy-admins`). Document the recommendation in the schema's `authorization` block as the install default; admins can adjust via the OpenRegister authorization UI.
- [ ] 7.2 For `publicationConsent`, schema-level RBAC remains as today (consent-officer role can write). The consent service enforces an additional service-level check at write time: writing a `scope: "entity"` record requires membership in the standing-consent group (e.g. `docudesk-standing-consent-admins`). This is enforced before save, not at the schema level.
- [ ] 7.3 Smoke-test that an unprivileged user cannot POST to `publicationProhibition` (403 expected per existing OR RBAC behavior). Smoke-test that a consent-officer can POST `scope: "document"` records but is rejected when attempting `scope: "entity"`.

## 8. Documentation

- [ ] 8.1 Add a new section to `docs/features/publication-consent-process.md` describing the policy layer: the `publicationProhibition` schema, the `scope: "entity"` flavor of `publicationConsent`, conflict resolution, retroactive behavior, UI toggle semantics, and the three admin surfaces. Diagram the three-layer model (prohibition → standing consent → workflow) and the four discriminator combinations of `policyMatch` × `notificationStatus`.
- [ ] 8.2 Add a CHANGELOG entry under "Added": new `publicationProhibition` schema; new `scope` discriminator and entity-scope field set on `publicationConsent`; new `policyMatch` polymorphic reference field; new "Standing Publication Consents" and "Publication Prohibitions" admin pages.
- [ ] 8.3 Add a CHANGELOG entry under "Behavior changes": detection-time policy lookup may pre-empt the WOO workflow inside publication-clearance flows. Existing consent-management consumers see the same `consentStatus` enum (no new values); the policy-pre-empted distinction lives in `policyMatch` + `notificationStatus: "skipped"`. Generic anonymisation flows are unaffected.
- [ ] 8.4 Verify ADR-001 (data via OpenRegister), ADR-008 (controller→service→mapper), ADR-012 (nextcloud-vue components), ADR-013 (loadable register templates), ADR-016 (seed data). All apply; no amendments needed. Document confirmation.

## 9. Testing — integration

- [ ] 9.1 Newman/Postman integration tests for the policy CRUD endpoints — both the new `publicationProhibition` schema and the `scope: "entity"` flavor of `publicationConsent`.
- [ ] 9.2 Newman tests for the four detection-time outcomes — no match (WOO flow); prohibition match (anonymized + policyMatch → prohibition); standing-consent match (consent_given + policyMatch → standing consent); both match (prohibition wins).
- [ ] 9.3 Newman tests for retroactive force-resolve: create an in-flight `scope: "document"` `publicationConsent` with `pending` status; create a matching prohibition; verify the existing record transitions to `anonymized` + `policyMatch` populated.
- [ ] 9.4 Newman tests for retroactive non-application of standing consents: create an in-flight `scope: "document"` `publicationConsent` with `objection_received` status; create a matching standing consent; verify the existing record stays as-is.
- [ ] 9.5 Schema validation test: attempt to save a `publicationConsent` record with `policyMatch` pointing to a non-policy schema (e.g. a `template` UUID) — verify rejection with appropriate error. Attempt to save with `policyMatch` pointing to a `publicationConsent` record whose `scope` is `document` — verify the consent service rejects with the scope-validation error.

## 10. Quality and verification

- [ ] 10.1 Run `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan, unit tests) — clean. Fix any pre-existing issues encountered in touched files per the workflow rule.
- [ ] 10.2 Manual smoke against a live stack: `curl` the consent endpoints with and without policy matches; verify the four outcomes match the spec; verify the retroactive flow; verify the UI toggle behavior on each of the three admin pages.
- [ ] 10.3 Run `/opsx:verify entity-publication-policies` to confirm artifacts ↔ code agreement.
