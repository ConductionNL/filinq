## 1. Schema additions in `docudesk_register.json`

- [ ] 1.1 Add the `mandatoryAnonymization` schema definition to `lib/Settings/docudesk_register.json` under the `consent` register's `schemas` block. Properties: `primaryName`, `entityType` (enum PERSON/ORGANIZATION/OTHER), `matchRules` (array of `{type, value}` with `type` enum: exact, normalized, bsn, kvk), `reason`, `legalAuthority`, `caseReference`, `severity` (enum), `jurisdiction`, `addedBy` (user ref), `validFrom`, `validUntil`, `active`, `notes` (markdown). Required: `primaryName`, `entityType`, `matchRules`, `reason`, `active`.
- [ ] 1.2 Add the `publicationAllowance` schema definition to `lib/Settings/docudesk_register.json` under the same register. Properties: `primaryName`, `entityType`, `matchRules` (same enum), `reason`, `consentDocument` (file ref), `consentMethod` (enum: paper / digital_signature / verbal_recorded / opt_in_form), `consentScope`, `addedBy`, `validFrom`, `validUntil`, `active`, `notes`. Required: `primaryName`, `entityType`, `matchRules`, `reason`, `consentMethod`, `active`.
- [ ] 1.3 Extend the existing `publicationConsent` schema's `consentStatus` enum with `mandatory_anonymized` and `blanket_consent_given`.
- [ ] 1.4 Add the new `policyMatch` property to the `publicationConsent` schema definition. Use `type: "object"`, `oneOf` with `$ref` to the two policy schemas, and `objectConfiguration.handling: "related-object"`. Property is OPTIONAL (not in the schema's required list).
- [ ] 1.5 Verify the schema imports cleanly via `composer test:unit` (or equivalent) — no JSON syntax errors, no schema-validation rejections.

## 2. Seed data per ADR-016

- [ ] 2.1 Add 4 realistic `mandatoryAnonymization` seed objects (per design.md Seed Data section): court order, minor protection, undercover officer, categorical privacy-board exemption. Use the `@self` envelope per ADR-013 with stable slugs.
- [ ] 2.2 Add 4 realistic `publicationAllowance` seed objects: mayor blanket consent, organization signed opt-in, council member, recorded verbal consent. Use the `@self` envelope.
- [ ] 2.3 Verify seed import is idempotent — re-running the import skips existing objects matched by slug. Smoke test: install on a clean instance, run the importer twice.

## 3. Detection-time matching service

- [ ] 3.1 Create `PolicyMatchService` (or extend the existing consent service) that loads all `active: true` rules from both schemas into an in-memory lookup index at service init. Index keys: `(matchType, entityType, value)` tuples for O(1) lookup. The service exposes a single method `match(entityText, entityType, resolvedIdentifiers): ?MatchedRule` that returns the highest-priority match or null.
- [ ] 3.2 Implement match-order semantics: deny rules consulted first, then allow. The service returns at most one match. On multi-deny match, return the rule with the lowest UUID lexicographically (deterministic).
- [ ] 3.3 Implement time-bound checking at match time: a rule with future `validFrom`, past `validUntil`, or `active: false` MUST NOT match.
- [ ] 3.4 Implement match types `exact`, `normalized`, `bsn`, `kvk`. `normalized` strips case and accents (use `Transliterator` or equivalent). Reject unknown types in the matcher (defense-in-depth — schema rejects them at write time, but the matcher logs and skips on unknown).
- [ ] 3.5 Subscribe the service to OpenRegister's object-changed events for the two policy schemas. On any change (insert/update/delete/active flip), invalidate and rebuild the in-memory index.
- [ ] 3.6 Unit-test the match service: each match type, time bounds, active flag, multi-rule precedence, multi-deny tie-break, deny-vs-allow conflict.

## 4. Consent service integration

- [ ] 4.1 Extend `ConsentService::createConsentRequest()` (or its caller) to consult `PolicyMatchService` before defaulting to the WOO workflow. On deny match: create a `publicationConsent` with `consentStatus: "mandatory_anonymized"`, `publicationDecision: "anonymize"`, `notificationStatus: "skipped"`, `objectionDeadline: null`, `policyMatch` referencing the rule UUID. On allow match: create with `consentStatus: "blanket_consent_given"`, `publicationDecision: "publish_with_consent"`, `notificationStatus: "skipped"`, `objectionDeadline: null`, `policyMatch` referencing the rule. On no match: existing WOO behavior unchanged.
- [ ] 4.2 Block status transitions FROM `mandatory_anonymized` and `blanket_consent_given` TO any WOO state in the consent-update path. Allowed: update `policyMatch` if the underlying rule changes (still pointing at a policy schema).
- [ ] 4.3 Unit-test the consent service for the four detection-time outcomes: no match → WOO; deny match → mandatory_anonymized; allow match → blanket_consent_given; both match → mandatory_anonymized (deny wins) with audit log capturing both rule UUIDs.

## 5. Retroactive handling on rule changes

- [ ] 5.1 On `mandatoryAnonymization` rule INSERT (or update making it match more entities — adding a new matchRule, flipping active to true, extending validUntil), the rule-mutation listener MUST find all in-flight `publicationConsent` records (status not in {`anonymized`, `mandatory_anonymized`, `blanket_consent_given`, terminal published states}) for matching entities and force-resolve them: `consentStatus: "mandatory_anonymized"`, `publicationDecision: "anonymize"`, `policyMatch` referencing the new rule. Cancel any pending notification dispatch (or no-op if already sent — the entity has the email but the workflow is moot now).
- [ ] 5.2 On `publicationAllowance` rule INSERT or update, do NOT modify in-flight `publicationConsent` records. Future detections benefit; past detections respect what was already decided. Document this asymmetry inline.
- [ ] 5.3 On rule DELETE or expiry (validUntil reached, active flipped to false), do NOT modify any past `publicationConsent` record. Past records keep their final state. Future detections fall through.
- [ ] 5.4 Unit-test retroactive force-resolve: in-flight `pending` and `consent_given` records both transition correctly; in-flight `objection_received` records ALSO transition (deny-list overrides explicit objection — privacy default is `anonymize`, which is what an objection would lead to anyway); `notificationSentAt` is preserved for audit even though `objectionDeadline` is cleared.

## 6. UI toggle behavior

- [ ] 6.1 In the Vue 2 publication-prep screen, render the anonymization toggle for each detected entity using the `consentStatus` of its `publicationConsent` record. For `mandatory_anonymized`: toggle ON, disabled, with tooltip explaining the entity is on the mandatoryAnonymization list. For `blanket_consent_given`: toggle OFF (default), enabled, user can flip ON to anonymize anyway. For other statuses: existing behavior.
- [ ] 6.2 When the user flips the override-up toggle for a `blanket_consent_given` record (anonymize-anyway), record the override as a status transition — emit an audit event and update `publicationDecision: "anonymize"` while keeping `consentStatus: "blanket_consent_given"` (the policy match is preserved; only the per-document decision is overridden).
- [ ] 6.3 Use `@conduction/nextcloud-vue` components per ADR-012 (CnFormDialog for any add-rule UI, CnDataTable for list views). No custom equivalents.
- [ ] 6.4 Add a CnIndexPage view for managing both policy lists (one tab per schema). Read-only by default; write access gated by RBAC.
- [ ] 6.5 Add browser tests (existing Playwright/Jest infra) covering: locked toggle for mandatory_anonymized; defaulted-off-overridable toggle for blanket_consent_given; existing flow unchanged for unmatched entities.

## 7. RBAC configuration

- [ ] 7.1 Configure default RBAC for both schemas. Recommended: `read` open to authenticated users in the consent group; `write` restricted to a privileged group (e.g. `docudesk-policy-admins`). Document the recommendation in the schema's `authorization` block as the install default; admins can adjust via the OpenRegister authorization UI.
- [ ] 7.2 Smoke-test that an unprivileged user cannot POST to either policy schema (403 expected per existing OR RBAC behavior).

## 8. Documentation

- [ ] 8.1 Add a new section to `docs/features/publication-consent-process.md` describing the policy layer: deny list, allow list, conflict resolution, retroactive behavior, UI toggle semantics. Diagram the three-layer model (policy → workflow → publication).
- [ ] 8.2 Add a CHANGELOG entry under "Added": new `mandatoryAnonymization` and `publicationAllowance` schemas; new `consentStatus` enum values; new `policyMatch` reference field on `publicationConsent`.
- [ ] 8.3 Add a CHANGELOG entry under "Behavior changes": detection-time policy lookup may pre-empt the WOO workflow. Existing consent-management consumers SHOULD handle the two new `consentStatus` values; treating them as terminal states (no workflow possible) is a safe default.
- [ ] 8.4 Verify ADR-001 (data via OpenRegister), ADR-008 (controller→service→mapper), ADR-012 (nextcloud-vue components), ADR-013 (loadable register templates), ADR-016 (seed data). All apply; no amendments needed. Document confirmation.

## 9. Testing — integration

- [ ] 9.1 Newman/Postman integration tests for the policy CRUD endpoints (both schemas).
- [ ] 9.2 Newman tests for the four detection-time outcomes — no match (WOO flow); deny match (mandatory_anonymized); allow match (blanket_consent_given); both match (deny wins).
- [ ] 9.3 Newman tests for retroactive force-resolve: create an in-flight publicationConsent with `pending` status; create a matching deny rule; verify the existing record transitions to `mandatory_anonymized`.
- [ ] 9.4 Newman tests for retroactive non-application of allow rules: create an in-flight publicationConsent with `objection_received` status; create a matching allow rule; verify the existing record stays as-is.
- [ ] 9.5 Schema validation test: attempt to save a `publicationConsent` record with `policyMatch` pointing to a non-policy schema (e.g. a `template` UUID) — verify rejection with appropriate error.

## 10. Quality and verification

- [ ] 10.1 Run `composer check:strict` (PHPCS, PHPMD, Psalm, PHPStan, unit tests) — clean. Fix any pre-existing issues encountered in touched files per the workflow rule.
- [ ] 10.2 Manual smoke against a live stack: `curl` the consent endpoints with and without policy matches; verify the four outcomes match the spec; verify the retroactive flow; verify the UI toggle behavior.
- [ ] 10.3 Run `/opsx:verify entity-publication-policies` to confirm artifacts ↔ code agreement.
