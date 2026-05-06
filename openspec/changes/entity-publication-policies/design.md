## Context

DocuDesk's existing `publicationConsent` schema (in the consent register, see `lib/Settings/docudesk_register.json`) tracks per-(document, detected-entity) workflow state for WOO compliance: notification → 28-day objection window → consent or anonymization decision. Each detected PERSON or ORGANIZATION in a publication-bound document spawns a fresh `publicationConsent` record that drives this workflow.

This change introduces a **policy layer** that pre-empts the per-document workflow at detection time. Two policy surfaces exist:

1. **Prohibitions** — entity-level deny rules ("Jan Janssen must never be published unredacted"), modeled as a new `publicationProhibition` schema.
2. **Standing consents** — entity-level allow rules ("Mayor De Vries has signed standing publication consent"), modeled as `publicationConsent` records with `scope: "entity"` (instead of the existing `scope: "document"` workflow records).

The trigger boundary is precise: the policy layer is consulted only by the publication-clearance entry point (`ConsentService::createConsentRequest()` and its caller). Generic anonymisation flows (sanitisation of files not destined for publication) do not call this entry point, do not create `publicationConsent` records, and therefore do not consult these policies. Per the canonical `consent-management` spec (CONS-048 / CONS-050), no automated trigger calls `createConsentRequest()` today — invocation is programmatic only. The publication-prep flow that will drive this in production is a separate change. This design assumes the entry point and specifies what it does when called.

OpenRegister's `ValidateObject` pipeline already supports constrained polymorphic references via `items.oneOf` + `$ref` with `objectConfiguration.handling: "related-object"`. This change uses that mechanism to make the new `publicationConsent.policyMatch` field type-safe (UUID must point to a `publicationProhibition` record OR a `scope: "entity"` `publicationConsent` record, nothing else). No OpenRegister code changes are required.

## Goals / Non-Goals

**Goals:**

- Represent prohibitions as a first-class schema (`publicationProhibition`).
- Represent standing consents as a `scope: "entity"` flavor of the existing `publicationConsent` schema, reusing the consent vocabulary rather than introducing a parallel "allowance" concept.
- Pre-empt the WOO per-document workflow at detection time when a policy matches.
- Make policy matches visible in the consent register: per-document records carry a typed reference (`policyMatch`) back to the prohibition or standing-consent record that fired, plus `notificationStatus: "skipped"` to indicate the workflow did not run.
- Provide a UI toggle whose state is derived from `policyMatch` referent type: locked-on for prohibition matches, defaulted-off-but-overridable for standing-consent matches.
- Support a small mix of match types — `exact`, `normalized`, `bsn`, `kvk` — that covers the common cases without the foot-guns of regex.
- Keep retroactive behavior asymmetric and conservative: prohibition additions force-resolve in-flight workflows; standing-consent additions don't override existing objections.

**Non-Goals:**

- Match types `regex` and `reference` — v2; deferred for false-positive risk and cross-register coupling concerns.
- Formal approval workflow for writing prohibitions or standing consents — RBAC for v1; separate change later.
- Retroactive sweep of already-published documents — never. Past publications are audit-only.
- Modifying OpenRegister — the polymorphic-reference pattern is already supported.
- Building the publication-prep flow that calls `createConsentRequest()` — separate change.
- Generic anonymisation surfaces (file sanitisation) — explicitly outside the trigger boundary; these do not interact with the policy layer.
- New `consentStatus` enum values — discriminator is `policyMatch` + `notificationStatus`, not a new status.

## Decisions

### 1. Standing consent folds into `publicationConsent`; prohibition stays separate

A standing publication consent ("Mayor De Vries has signed off on being named in publications") is semantically a long-lived `publicationConsent.consentStatus = "consent_given"` — same intent as a per-document consent reply, just at entity granularity with a validity window. Modeling it as a `scope: "entity"` flavor of `publicationConsent` reuses the existing consent vocabulary and avoids a parallel "allowance" schema that would carry the same semantics under a different name.

`publicationProhibition` stays separate. A prohibition is not a consent — it asserts the absence of permission, with its own metadata (legal authority, severity, jurisdiction, case reference) and its own UI semantics (lock-the-toggle, no override). Forcing it into `publicationConsent` would require a `policyType` discriminator with conditional required fields and per-value RBAC — uglier than just keeping it separate.

The schema decomposition after this change:

```
consent register
├── publicationConsent
│   ├── scope: "document"     existing per-doc workflow records
│   └── scope: "entity"       NEW. Standing consents.
└── publicationProhibition    NEW. Entity-level deny rules.
```

`publicationConsent` gains a `scope` discriminator (default: `document`, for backwards compatibility with all existing records). For `scope: "document"`, the field set and required-fields are unchanged. For `scope: "entity"`, additional fields are introduced (`matchRules`, `validFrom`, `validUntil`, `active`, `consentMethod`, `consentDocument`, `consentScope`), and `documentId` plus the workflow fields (`notificationStatus`, `notificationSentAt`, `objectionDeadline`, `objectionReceivedAt`, `objectionReason`, `publicationDecision`) are not used.

### 2. Polymorphic-but-constrained `policyMatch` reference

The new `publicationConsent.policyMatch` field uses `items.oneOf` + `$ref` to constrain to exactly the two referent types:

```json
{
  "policyMatch": {
    "type": "object",
    "oneOf": [
      { "$ref": "<publicationProhibition-schema-uuid>" },
      { "$ref": "<publicationConsent-schema-uuid>" }
    ],
    "objectConfiguration": { "handling": "related-object" }
  }
}
```

`ValidateObject::extractObjectConfigurationHandling` walks `items.oneOf` and applies the constraint at save time — a UUID pointing to anything other than these two schemas is rejected. The self-reference (publicationConsent → publicationConsent) is unusual but valid: a `scope: "document"` record references the `scope: "entity"` record that pre-empted it. The match-time service ensures the referent is in fact a `scope: "entity"` record; the schema constraint is "must be a publicationConsent" and the service tightens it further at write time.

Consumers reading a per-document record can determine the path of arrival without inspecting `consentStatus` semantics:

| Outcome | `consentStatus` | `notificationStatus` | `policyMatch` referent |
|---|---|---|---|
| Workflow → entity consented | `consent_given` | `delivered` | `null` |
| Workflow → entity objected | `objection_received` | `delivered` | `null` |
| Workflow → no reply, decided to anonymise | `anonymized` | `delivered` | `null` |
| Prohibition pre-empted workflow | `anonymized` | `skipped` | `publicationProhibition` |
| Standing consent pre-empted workflow | `consent_given` | `skipped` | `publicationConsent` (scope=entity) |

No new `consentStatus` values are introduced. The discrimination lives in the polymorphic reference plus `notificationStatus`.

### 3. Match type set: `exact`, `normalized`, `bsn`, `kvk` only at v1

Trade-off:

| Type | Pros | Cons | v1? |
|---|---|---|---|
| `exact` | Simple, deterministic | Brittle ("J. Smith" ≠ "John Smith") | ✓ |
| `normalized` | Case/accent insensitive | Still string-only | ✓ |
| `bsn` | Strongest match for PERSON in NL | Detector must resolve entity → BSN (often does for named persons in identity-bearing documents) | ✓ |
| `kvk` | Strongest match for ORGANIZATION in NL | Detector must resolve entity → KvK | ✓ |
| `regex` | Powerful for class rules | Severe false-positive and ReDoS risk | ✗ defer to v2 |
| `reference` | Link to a Person/Org object in BRP/KvK register | Cross-register dependency, harder UX | ✗ defer to v2 |

`exact` and `normalized` cover names. `bsn` and `kvk` cover identifier-resolved entities. This handles the strong-identity case (court-protected witnesses with known BSN) and the weak-identity case (a published-name match against a list).

### 4. Detector consults a flattened in-memory rule cache

Per-row JSON-array scan over `matchRules` would be N+1-ish per detection. Instead, the consent service loads at startup (or on rule-mutation event):

- All `active: true` `publicationProhibition` records.
- All `active: true` `publicationConsent` records where `scope: "entity"` and time bounds are open.

Both are flattened into a per-(matchType, entityType) lookup index in memory. Detection-time match is O(1) per check.

Cache invalidation:
- On any write to either source (insert/update/delete/active flag flip), an event is emitted (OpenRegister already emits object-changed events).
- The consent service listener invalidates and rebuilds the cache.
- The listener filters `publicationConsent` events by `scope` — a write to a `scope: "document"` record (i.e., a normal workflow operation) does not invalidate the standing-consent cache.
- For multi-process deployments, an APCu/Redis pub-sub fan-out signals other workers (out of scope here; can use existing OR cache-invalidation infrastructure if available).

Expected list size: tens to low hundreds of entries per organization. Cache fits comfortably in memory; rebuild cost is trivial.

### 5. Conflict resolution: prohibition wins, no exceptions

If an entity matches both a `publicationProhibition` rule and an active standing `publicationConsent`, the resulting per-document `publicationConsent` is `consentStatus: "anonymized"` with `policyMatch` pointing at the prohibition. The matching standing consent is recorded in audit logs (not in the per-document record itself — `policyMatch` is single-valued).

Rationale: privacy-safest, hardest to misuse. A standing consent is a permission *to* publish; a prohibition is a permission *not to* — and the absence of permission to publish wins by default. A configurable override would be a footgun: an org admin could flip a setting and accidentally unmask a protected witness.

### 6. Retroactive behavior, asymmetric by design

The asymmetry reflects intent — prohibition is "must always anonymize", standing consent is "may publish":

| Event | In-flight `scope: "document"` `publicationConsent` records |
|---|---|
| Prohibition added | Force-resolve matching records: `consentStatus: "anonymized"`, `notificationStatus: "skipped"`, `publicationDecision: "anonymize"`, `policyMatch` populated. Cancel any pending notification dispatch. Reason: "must always" beats current workflow state. |
| Standing consent added | **Leave alone.** An entity who already submitted an objection during the WOO window has stated an explicit wish; an after-the-fact standing consent should not override that. Future detections benefit. |
| Prohibition or standing consent removed / expired / deactivated | Past records keep their final state (audit). Future detections fall through to the next layer. |
| Already-published documents (any rule change) | Never touched. Optional audit report can flag "documents containing now-prohibited entities" for human review. |

### 7. UI toggle: keyed off `policyMatch` referent type

The frontend anonymization toggle (per-entity, in the publication-prep screen) reflects the polymorphic referent of `policyMatch`, NOT a consentStatus value:

| `policyMatch` referent | Toggle default | User can override? |
|---|---|---|
| `null` | per existing UX based on `consentStatus` | yes |
| `publicationProhibition` | **on, locked** | **no** |
| `publicationConsent` (scope=entity) | **off, defaulted** | **yes — user can flip on to anonymize anyway** |

The override-up case for standing consents exists because publication editors sometimes have document-specific reasons to anonymize even an entity with standing consent (e.g. a draft mentioning a public official in an unflattering or sensitive context where the official's standing consent feels implicit but not appropriate to that piece). The toggle reflects this — it's defaulted off, but flipping it on reverts the decision to a normal anonymization flow. The override is recorded as an update to `publicationDecision` (set to `"anonymize"`) while `consentStatus` stays `consent_given` and `policyMatch` is preserved (the standing consent still matched; only the per-document decision was overridden).

### 8. RBAC for v1; approval workflow deferred

Adding to either policy surface is sensitive. v1 relies on RBAC: privileged users in a designated group can write directly. Both `publicationProhibition` records and `scope: "entity"` `publicationConsent` records are write-protected. The schema-level RBAC for `publicationConsent` does not distinguish by `scope`; the service-level write path enforces "you may not create a `scope: "entity"` record without standing-consent write permission" by checking group membership before save.

Approval workflow (one user proposes, another approves; with state machine, audit, and rollback) is a separate, larger change. Track as a follow-up after these surfaces are in production and we can observe how org admins actually use them.

### 9. Three separate admin surfaces

The records-list UI splits cleanly along the policy/workflow axis:

- **Consent Workflow** — the existing per-document consent records list. Filtered by `scope: "document"`. No structural changes; gains a "policy pre-empted" indicator on rows whose `policyMatch` is non-null.
- **Standing Publication Consents** — NEW. Filtered view of `publicationConsent` where `scope: "entity"`. List, edit, expire, revoke standing consents. The form for creating a record requires `consentMethod` and surfaces a UI warning when `validUntil` is left blank (signed consent typically has a finite term).
- **Publication Prohibitions** — NEW. CRUD for `publicationProhibition` records. The form encourages adding stable identifiers (BSN/KvK) where possible and warns loudly when only a name-based rule is added.

Three pages keep the mental model explicit: per-document workflow, entity-wide standing consents, entity-wide prohibitions. A consolidated screen would conflate three different operational concerns (clearance ops, consent-officer workflow, legal/compliance).

## Risks / Trade-offs

### Conditional required fields on `publicationConsent`

`scope: "document"` records require `documentId`, `notificationStatus`, `consentStatus`, `publicationDecision`. `scope: "entity"` records require `matchRules`, `consentMethod`, `active`, plus `entityType` and a primary identifier (re-using `entityText` as the canonical name). The same schema accommodating both shapes means the top-level `required` array can hold only the universal fields (`scope`, `entityType`, `entityText`); the rest is enforced by the consent service at write time. **Mitigation:** the consent service rejects writes that violate the per-scope required-field set with a clear error; unit tests cover the four corners (each scope × valid/invalid).

### False matches via `normalized` rule

A name like "Jan Janssen" is common; a normalized exact-match rule would match every Jan Janssen, not just the protected one. **Mitigation:** the UI for adding a prohibition or standing consent must encourage adding stable identifiers (BSN/KvK) where possible and warn loudly when only a name-based rule is being added. Mitigation is documentation + UI affordance, not enforcement.

### Standing-consent misuse risk

A misconfigured standing consent could unmask personal data without proper consent — a more severe failure than a misconfigured prohibition. **Mitigation 1:** standing consents SHOULD have `validUntil` set (signed consent has shelf life). **Mitigation 2:** the UI for adding a standing consent requires a `consentDocument` upload (signed PDF or equivalent) and a `consentMethod` value. **Mitigation 3:** logging — every standing-consent match is logged with rule UUID + matched entity for audit.

### Cache invalidation lag in multi-worker deployments

Adding a rule must be reflected in detection within seconds. Pure in-process caches don't fan out across workers. **Mitigation:** rely on OpenRegister's existing object-changed event bus + a TTL-bounded fallback (cache rebuild every N seconds) so a worker that missed the event recovers within a bounded window. Acceptable for v1; if it proves problematic, upgrade to explicit pub/sub.

### Polymorphic reference complexity for downstream consumers

Frontend / external consumers of `publicationConsent` may not expect `policyMatch` to be either of two object types. **Mitigation:** the canonical decision table (Decision 2) documents the four discriminator combinations clearly. Consumers that don't recognize `policyMatch` can treat any non-null value as "policy pre-empted, terminal" — still valid.

### Self-referencing `policyMatch` (publicationConsent → publicationConsent)

A per-document record references the entity-scope record that pre-empted it. Self-referencing schemas can confuse some consumers (graph traversals, naive serializers). **Mitigation:** the convention is unambiguous — referer is always `scope: "document"`, referent is always `scope: "entity"`. The matching service enforces this at write time; downstream consumers can rely on it. A traversal that follows `policyMatch` recursively will terminate after one hop because `scope: "entity"` records have `policyMatch: null` by definition.

### "Reference" match type deferred

Deferring `reference` (link to a Person/Org object in BRP/KvK register) means we can't say "this BSN-keyed person in BRP is protected" with one rule — admins must duplicate the BSN into the prohibition. **Mitigation:** acceptable for v1. Reference-based matching introduces cross-register lookup latency and stale-cache concerns that deserve their own design pass.

### Conflict resolution gives implicit precedence to the most recent prohibition

If two prohibitions match (e.g. one by exact name, one by BSN), the audit trail might lose information about which rule matched first. **Mitigation:** the matching service records ALL matched rule UUIDs in the audit log, not just the one that wins. The `policyMatch` field on the per-document record records the first prohibition match (deterministic order: rule UUID ascending). This is consistent with the prohibition-as-policy-set semantics — multiple prohibitions saying "always anonymize" is reinforcing, not conflicting.

## Migration Plan

No data migration needed. Roll-out:

1. Land schema changes in `lib/Settings/docudesk_register.json`. The `publicationConsent` schema gains the `scope` field (default `document`, applied to all existing records on load) plus the new optional fields. The new `publicationProhibition` schema comes up empty.
2. Detection service starts consulting empty prohibition + standing-consent caches — initially every detection falls through to existing WOO workflow. Behavior is unchanged.
3. Privileged users populate the prohibition list with known protected entities. Each addition triggers cache reload + retroactive force-resolve of in-flight per-document records.
4. (Later) Privileged users populate standing consents with prior-consent entities. Each addition leaves in-flight per-document records alone; future detections benefit.
5. Frontend toggle behavior ships in the same release as the schema changes; users see the new locked/defaulted states immediately on records whose `policyMatch` is non-null.

No flag/gate needed — the absence of policy records is functionally equivalent to "no policy active." The change is backwards-compatible from the moment it lands.

## Seed Data

Per ADR-016. Both new policy surfaces (the `publicationProhibition` schema and the `scope: "entity"` flavor of `publicationConsent`) need 3-5 realistic seed objects. The seed data is realistic across organization types (municipality, consultancy, travel agency) and demonstrates the variety of legitimate uses.

### Seed for `publicationProhibition` (4 records)

```json
[
  {
    "@self": { "register": "consent", "schema": "publicationProhibition", "slug": "court-order-2024-0312" },
    "primaryName": "Beschermde Getuige A",
    "entityType": "PERSON",
    "matchRules": [
      { "type": "exact", "value": "Beschermde Getuige A" },
      { "type": "normalized", "value": "beschermde getuige a" }
    ],
    "reason": "Getuigenbescherming op grond van rechterlijke beslissing.",
    "legalAuthority": "Rechtbank Amsterdam",
    "caseReference": "RB-AMS 2024-0312",
    "severity": "threat-assessment",
    "jurisdiction": "NL national",
    "addedBy": null,
    "validFrom": "2024-03-15T00:00:00+00:00",
    "validUntil": "2027-03-15T00:00:00+00:00",
    "active": true,
    "notes": "Periodieke herziening volgens beslissing rechter-commissaris."
  },
  {
    "@self": { "register": "consent", "schema": "publicationProhibition", "slug": "minor-protection-stichting-jeugd" },
    "primaryName": "Cliënten Stichting Jeugd & Veiligheid",
    "entityType": "ORGANIZATION",
    "matchRules": [
      { "type": "kvk", "value": "12345678" }
    ],
    "reason": "Stichting verzorgt opvang van minderjarigen; openbaarmaking van cliëntgegevens onverenigbaar met AVG art. 8 (kindergegevens).",
    "legalAuthority": "Functionaris Gegevensbescherming gemeente",
    "caseReference": "FG-2024-MINOR-007",
    "severity": "minor-protection",
    "jurisdiction": "municipal",
    "addedBy": null,
    "validFrom": "2024-01-01T00:00:00+00:00",
    "validUntil": null,
    "active": true,
    "notes": "Open-eindig — herziening jaarlijks."
  },
  {
    "@self": { "register": "consent", "schema": "publicationProhibition", "slug": "undercover-officer-jansen" },
    "primaryName": "Politiemedewerker undercover (Jansen)",
    "entityType": "PERSON",
    "matchRules": [
      { "type": "bsn", "value": "111222333" },
      { "type": "exact", "value": "P. Jansen" },
      { "type": "exact", "value": "Pieter Jansen" }
    ],
    "reason": "Operationele veiligheid — undercover-functie. Identiteit mag niet gekoppeld worden aan publieke documenten.",
    "legalAuthority": "Korpschef Politie Eenheid Noord-Holland",
    "caseReference": "KORPS-VEILIG-2025-019",
    "severity": "security",
    "jurisdiction": "NL national",
    "addedBy": null,
    "validFrom": "2025-06-01T00:00:00+00:00",
    "validUntil": "2026-12-31T00:00:00+00:00",
    "active": true,
    "notes": "Verlenging vereist actieve bevestiging vóór einddatum."
  },
  {
    "@self": { "register": "consent", "schema": "publicationProhibition", "slug": "privacy-board-categorial-minors" },
    "primaryName": "Categorie: minderjarigen in slachtofferdossiers",
    "entityType": "PERSON",
    "matchRules": [
      { "type": "normalized", "value": "[minderjarige slachtoffer]" }
    ],
    "reason": "Categorische uitzondering — minderjarige slachtoffers in zaakdossiers worden altijd gepseudonimiseerd voorafgaand aan publicatie.",
    "legalAuthority": "Privacy Board",
    "caseReference": "PB-CAT-002",
    "severity": "minor-protection",
    "jurisdiction": "EU",
    "addedBy": null,
    "validFrom": "2024-05-25T00:00:00+00:00",
    "validUntil": null,
    "active": true,
    "notes": "Categorische regel, geen specifiek individu — gebruikt wanneer detector een placeholder-tekst voor minderjarige slachtoffers herkent."
  }
]
```

### Seed for standing consents (`publicationConsent` with `scope: "entity"`, 4 records)

```json
[
  {
    "@self": { "register": "consent", "schema": "publicationConsent", "slug": "mayor-de-vries-blanket-consent" },
    "scope": "entity",
    "entityType": "PERSON",
    "entityText": "Burgemeester De Vries",
    "matchRules": [
      { "type": "exact", "value": "Burgemeester De Vries" },
      { "type": "exact", "value": "Mevrouw De Vries" },
      { "type": "exact", "value": "Karin de Vries" }
    ],
    "consentStatus": "consent_given",
    "consentMethod": "opt_in_form",
    "consentDocument": null,
    "consentScope": "Alleen documenten betreffende uitoefening van de burgemeestersfunctie. Privé-aangelegenheden vallen buiten de toestemming.",
    "legalBasis": "Publieke functie — staande publicatietoestemming voor handelingen in officiële capaciteit.",
    "validFrom": "2024-09-01T00:00:00+00:00",
    "validUntil": "2028-09-01T00:00:00+00:00",
    "active": true,
    "notes": "Termijn loopt parallel aan de termijn als burgemeester."
  },
  {
    "@self": { "register": "consent", "schema": "publicationConsent", "slug": "kvk-zaak-en-partner-bv" },
    "scope": "entity",
    "entityType": "ORGANIZATION",
    "entityText": "Zaak & Partner B.V.",
    "matchRules": [
      { "type": "kvk", "value": "87654321" },
      { "type": "exact", "value": "Zaak & Partner B.V." },
      { "type": "exact", "value": "Zaak en Partner" }
    ],
    "consentStatus": "consent_given",
    "consentMethod": "digital_signature",
    "consentDocument": null,
    "consentScope": "Documenten in het kader van het programma 'Open Bestuur 2025-2028'.",
    "legalBasis": "Vennootschap heeft als adviseur voor het publicatieprogramma getekend voor opname in publieke documenten zonder per-document notificatie.",
    "validFrom": "2025-01-15T00:00:00+00:00",
    "validUntil": "2028-12-31T00:00:00+00:00",
    "active": true,
    "notes": null
  },
  {
    "@self": { "register": "consent", "schema": "publicationConsent", "slug": "councilmember-bakker" },
    "scope": "entity",
    "entityType": "PERSON",
    "entityText": "Raadslid Bakker (gemeente Voorbeeld)",
    "matchRules": [
      { "type": "exact", "value": "S. Bakker" },
      { "type": "exact", "value": "Sylvia Bakker" },
      { "type": "bsn", "value": "999888777" }
    ],
    "consentStatus": "consent_given",
    "consentMethod": "paper",
    "consentDocument": null,
    "consentScope": "Uitsluitend documenten gerelateerd aan raadswerkzaamheden in gemeente Voorbeeld.",
    "legalBasis": "Raadslid in actieve functie — opt-in toestemming voor publicatie in raadsverslagen, moties, amendementen.",
    "validFrom": "2024-03-15T00:00:00+00:00",
    "validUntil": "2026-03-15T00:00:00+00:00",
    "active": true,
    "notes": "Termijn herzien bij start nieuwe raadsperiode."
  },
  {
    "@self": { "register": "consent", "schema": "publicationConsent", "slug": "directeur-jansen-reisorg" },
    "scope": "entity",
    "entityType": "PERSON",
    "entityText": "Directeur M. Jansen",
    "matchRules": [
      { "type": "exact", "value": "Maartje Jansen" },
      { "type": "exact", "value": "M. Jansen (directeur)" }
    ],
    "consentStatus": "consent_given",
    "consentMethod": "verbal_recorded",
    "consentDocument": null,
    "consentScope": "Jaarverslagen en pers-uitingen — geen interne stukken.",
    "legalBasis": "Directeur van reisorganisatie heeft schriftelijke toestemming gegeven voor publicatie in jaarverslagen en pers-uitingen van moederorganisatie.",
    "validFrom": "2025-10-01T00:00:00+00:00",
    "validUntil": "2026-10-01T00:00:00+00:00",
    "active": true,
    "notes": "Termijn beperkt tot 1 jaar; herzien bij volgende jaargesprek."
  }
]
```

The `consentDocument` field is left `null` in seed data because the file references can't be created at seed-import time (no actual signed PDFs to attach). Production records would populate this with a real file UUID.

`addedBy` is `null` in seed — the import process attributes ownership to the system user.

The `legalBasis` field on standing-consent seeds carries what the prohibition seeds put in `reason` — i.e., the human-readable justification for why the consent was granted. For per-document records, `legalBasis` continues to record the legal basis for publication (e.g. "Wet Open Overheid art. 3.1") as it does today.
