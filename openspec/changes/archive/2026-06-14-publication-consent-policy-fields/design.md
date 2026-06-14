status: pr-created

## Context

`publicationConsent` today is per-(document, entity) workflow. To express "this entity has given blanket prior consent" and "this consent was pre-empted by a policy", the schema needs (a) a `scope` discriminator splitting per-document records from per-entity standing-consent records, and (b) a polymorphic `policyMatch` reference pointing at either a prohibition or a standing-consent record.

The new prohibition schema + detection-service + retroactive handler live in the sibling change `publication-prohibition-schema`. This change extends `publicationConsent` and the consent service to model + enforce the new shape.

## Goals / Non-Goals

**Goals:**

- `scope` discriminator on `publicationConsent` (default `document` for backward compatibility).
- Entity-scope field set for `scope: "entity"` records.
- Polymorphic `policyMatch` reference on `scope: "document"` records constrained to prohibition OR standing-consent.
- Service-level write validation gating the field set by `scope`.
- Standing-consent matching path in the consent service (entity match → `consentStatus: "consent_given"` + `policyMatch` populated + `notificationStatus: "skipped"`).
- Override-up flow on standing-consent matches: user may flip the per-entity toggle on to anonymise anyway.
- Three admin surfaces: existing Consent Workflow (filtered to `scope: "document"`); new Standing Publication Consents (filtered to `scope: "entity"`); the Publication Prohibitions page is the sibling change's concern.

**Non-Goals:**

- The prohibition schema + service + retroactive handler — sibling change.
- New `consentStatus` enum values — existing values cover all outcomes; the pre-empted distinction lives in `policyMatch` + `notificationStatus: "skipped"`.
- Approval workflow on adding standing consents — separate change.
- Match types `regex` / `reference` — v2.

## Decisions

### D1. `scope` default is `document` for backward compatibility

Existing records load with `scope: "document"` automatically. New writes default to `document` if absent.

### D2. Entity-scope fields not in schema-level `required`

Cross-field requiredness (matchRules + consentMethod when scope=entity; documentId when scope=document) is gated at the service level, not at the schema. Keeps the JSON Schema declarative + simple.

### D3. `policyMatch` polymorphic via OR's `oneOf` + `$ref`

`type: "object"`, `oneOf` with `$ref` to `publicationProhibition` and `publicationConsent`, `objectConfiguration.handling: "related-object"`. Validation enforced by OR's existing `ValidateObject` pipeline (`items.oneOf` polymorphic-reference path). No OR code changes.

### D4. `policyMatch` valid only on `scope: "document"` records

Service-level write validation rejects `policyMatch` on `scope: "entity"` records. A standing-consent record can't itself carry a `policyMatch` — the discriminator is one-way.

### D5. `policyMatch → publicationConsent` referent must be `scope: "entity"`

Service-level validation also rejects pointing `policyMatch` at a `scope: "document"` record. Keeps the polymorphism semantically clean.

### D6. No new `consentStatus` values

Pre-empted distinction: prohibition match → `anonymized` + `policyMatch → prohibition` + `notificationStatus: "skipped"`; standing-consent match → `consent_given` + `policyMatch → consent (scope=entity)` + `notificationStatus: "skipped"`. Consumers of `consentStatus` see the existing enum semantics.

### D7. Override-up audit on standing-consent matches

When the user flips the per-entity toggle ON for a standing-consent match, record as a status transition — emit an audit event and update `publicationDecision: "anonymize"` while keeping `consentStatus: "consent_given"` and preserving `policyMatch`. The standing consent still matched; only the per-document decision was overridden.

### D8. RBAC: service-level enforcement on `scope: "entity"` writes

Schema-level RBAC stays as today (consent-officer can write `publicationConsent`). Service adds a check at write time: writing a `scope: "entity"` record requires membership in `docudesk-standing-consent-admins`. Enforced before save.

## Risks / Trade-offs

- **Polymorphic reference in JSON Schema** → OR's `oneOf` + `$ref` pattern is already supported; no OR code changes. Documented dependency on the existing ValidateObject path.
- **Backward compatibility of consumers** → fields are additive; consumers that ignore unknown fields stay unaffected.
- **Service-level vs schema-level validation drift** → tests cover both paths explicitly.

## Migration Plan

1. Extend `publicationConsent` schema in `docudesk_register.json` (new fields + `policyMatch`).
2. Land service-level write validation.
3. Land standing-consent matching path in the consent service.
4. Land override-up flow + audit emission.
5. Build the "Standing Publication Consents" admin page; apply the `scope: "document"` filter to the existing Consent Workflow page.
6. Ship four standing-consent seed records.

**Rollback:** Records with `scope: "entity"` become orphan; the consent service falls back to document-only mode. Acceptable for emergency rollback.

## Seed Data

Four realistic standing-consent records as `publicationConsent` with `scope: "entity"`: mayor blanket consent, organisation signed opt-in, council member, recorded verbal consent. `consentStatus: "consent_given"`, `consentMethod` populated, `validFrom` / `validUntil` set, `active: true`, `legalBasis` populated with the human-readable justification.

## Open Questions

- Should the override-up flow require a typed reason from the operator? Provisional: optional in v1; future enhancement may require typed justification for audit.
