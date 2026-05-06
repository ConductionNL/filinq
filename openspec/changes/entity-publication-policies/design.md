## Context

DocuDesk's existing `publicationConsent` schema (in the consent register, see `lib/Settings/docudesk_register.json`) tracks per-(document, detected-entity) workflow state for WOO compliance: notification → 28-day objection window → consent or anonymization decision. Each detected PERSON or ORGANIZATION in a publication-bound document spawns a fresh `publicationConsent` record that drives this workflow.

This change introduces an orthogonal **policy** layer. Policies describe entity-level rules ("Jan Janssen must always be anonymized" / "Mayor De Vries has standing publication consent") that pre-empt the per-document workflow on detection. The policies are not workflow records — they are static rules that the detector consults before deciding whether to enter the WOO process.

OpenRegister's `ValidateObject` pipeline already supports constrained polymorphic references via `items.oneOf` + `$ref` with `objectConfiguration.handling: "related-object"`. This change uses that mechanism to make the new `publicationConsent.policyMatch` field type-safe (UUID must point to a `mandatoryAnonymization` or `publicationAllowance` record, nothing else). No OpenRegister code changes are required.

The detection-time integration point is DocuDesk's consent service (today: `lib/Service/ConsentService.php` and friends). The service currently calls `createConsentRequest()` per detected entity. The new behavior wraps this with a policy check: deny-list match → create record with `mandatory_anonymized` status; allow-list match → create record with `blanket_consent_given` status; no match → existing WOO flow.

## Goals / Non-Goals

**Goals:**

- Represent two entity-level publication policies (deny / allow) as first-class OpenRegister schemas.
- Pre-empt the WOO per-document workflow at detection time when a policy matches.
- Make policy matches visible in the consent register (the matched entity still shows up as a `publicationConsent` record, with status indicating policy pre-emption and a typed reference back to the matching rule for audit).
- Provide a UI toggle behavior that reflects the policy: deny → locked on, allow → defaults off, override-up permitted.
- Support a small mix of match types — exact, normalized, bsn, kvk — that covers the common cases without the foot-guns of regex.
- Keep retroactive behavior asymmetric and conservative: deny-list additions force-resolve in-flight workflows; allow-list additions don't override existing objections.

**Non-Goals:**

- Match types `regex` and `reference` — v2; deferred for false-positive risk and cross-register coupling concerns.
- Formal approval workflow for writing to the lists — RBAC for v1; separate change later.
- Retroactive sweep of already-published documents — never. Past publications are audit-only.
- Modifying OpenRegister — the polymorphic-reference pattern is already supported.
- Changes to other consent consumers (opencatalogi, etc.) — they only interact with `publicationConsent`, which gets additive enum values and an optional new field.
- A separate audit-history schema for "every match that ever fired" — the `publicationConsent` record itself is the audit trail (one record per document × entity × match).

## Decisions

### 1. Two separate schemas, not one polymorphic schema

`mandatoryAnonymization` and `publicationAllowance` are physically separate schemas, even though their matching half is symmetric. Reasons:

- Different metadata: deny-list records carry `legalAuthority`, `caseReference`, `severity`, `jurisdiction`. Allow-list records carry `consentDocument` (file ref), `consentMethod`, `consentScope`. A merged schema would need conditional fields based on a `policyType` discriminator — uglier in JSON Schema and harder to validate.
- Different RBAC: writing to the deny list is a privileged operation typically scoped to legal/compliance roles; writing to the allow list is typically scoped to publication officers. Per-schema RBAC is the cleanest mechanism.
- Different UI surfaces: a "protected entities" admin view differs from a "consent records" admin view. Forcing them into one screen would compromise both.

The matching half (`matchRules`, `entityType`, `primaryName`, `validFrom`, `validUntil`, `active`, `addedBy`, `notes`) is duplicated across the two schemas. Acceptable cost.

### 2. Polymorphic but constrained `policyMatch` reference

The new `publicationConsent.policyMatch` field uses `items.oneOf` + `$ref` to constrain to exactly the two policy schemas:

```json
{
  "policyMatch": {
    "type": "object",
    "oneOf": [
      { "$ref": "<mandatoryAnonymization-schema-uuid>" },
      { "$ref": "<publicationAllowance-schema-uuid>" }
    ],
    "objectConfiguration": { "handling": "related-object" }
  }
}
```

`ValidateObject::extractObjectConfigurationHandling` walks `items.oneOf` and applies the constraint at save time — a UUID pointing to anything other than these two schemas is rejected. The `consentStatus` enum value (`mandatory_anonymized` vs `blanket_consent_given`) carries the type information; consumers don't need to inspect the referenced object to know which policy fired.

### 3. Match type set: exact, normalized, bsn, kvk only at v1

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

Per-row JSON-array scan over `matchRules` would be N+1-ish per detection. Instead, the consent service loads all active deny + allow rules at startup (or on rule-mutation event) and flattens them into a per-(matchType, entityType) lookup index in memory. Detection-time match is O(1) per check.

Cache invalidation:
- On any write to either schema (insert/update/delete/active flag flip), an event is emitted (OpenRegister already emits object-changed events).
- The consent service listener invalidates and rebuilds the cache.
- For multi-process deployments, an APCu/Redis pub-sub fan-out signals other workers (out of scope here; can use existing OR cache-invalidation infrastructure if available).

Expected list size: tens to low hundreds of entries per organization. Cache fits comfortably in memory; rebuild cost is trivial.

### 5. Conflict resolution: deny wins, no exceptions

If an entity matches both a deny-list and an allow-list rule, the resulting `publicationConsent` is `mandatory_anonymized` with `policyMatch` pointing at the deny-list rule. The matching allow-list rule is recorded in audit logs (not in the `publicationConsent` itself — `policyMatch` is single-valued).

Rationale: privacy-safest, hardest to misuse. An allow-list match is a permission *to* publish, but a deny-list match is a permission *not to* — and the absence of permission to publish wins by default. A configurable override would be a footgun: an org admin could flip a setting and accidentally unmask a protected witness.

### 6. Retroactive behavior, asymmetric by design

The asymmetry reflects intent — deny is "must always anonymize", allow is "may publish":

| Event | In-flight `publicationConsent` records |
|---|---|
| Deny rule added | Force-resolve matching records to `mandatory_anonymized`; populate `policyMatch`. Cancel any pending notification. Reason: "must always" beats current workflow state. |
| Allow rule added | **Leave alone.** An entity who already submitted an objection during the WOO window has stated an explicit wish; an after-the-fact allow-list shouldn't override that. Future detections benefit. |
| Deny or allow rule removed / expired | Past records keep their final state (audit). Future detections fall through to the next layer. |
| Already-published documents (any rule change) | Never touched. Optional audit report can flag "documents containing now-deny-listed entities" for human review. |

### 7. UI toggle: locked for deny, override-up for allow

The frontend anonymization toggle (per-entity, in the publication-prep screen) reflects `consentStatus`:

| Status | Toggle default | User can override? |
|---|---|---|
| `pending` / `consent_given` / `objection_received` / `no_response` | per existing UX | yes |
| `anonymized` (existing terminal state) | on | no |
| `mandatory_anonymized` (new) | **on, locked** | **no** |
| `blanket_consent_given` (new) | **off, defaulted** | **yes — user can flip on to anonymize anyway** |

The override-up case for `blanket_consent_given` exists because publication editors sometimes have document-specific reasons to anonymize even an entity with blanket consent (e.g. a draft mentioning a public official in an unflattering or sensitive context where the official's standing consent feels implicit but not appropriate to that piece). The toggle reflects this — it's defaulted off, but flipping it on reverts the decision to a normal anonymization flow.

### 8. RBAC for v1; approval workflow deferred

Adding to either list is sensitive. v1 relies on RBAC: privileged users in a designated group can write directly. Both lists are write-protected in the same way as the existing `publicationConsent`.

Approval workflow (one user proposes, another approves; with state machine, audit, and rollback) is a separate, larger change. Track as a follow-up after these schemas are in production and we can observe how org admins actually use them.

## Risks / Trade-offs

### False matches via `normalized` rule

A name like "Jan Janssen" is common; a normalized exact-match rule would match every Jan Janssen, not just the protected one. **Mitigation:** the UI for adding a deny-list rule must encourage adding stable identifiers (BSN/KvK) where possible and warn loudly when only a name-based rule is being added. Mitigation is documentation + UI affordance, not enforcement.

### Allow-list misuse risk

A misconfigured allow-list rule could unmask personal data without proper consent — a more severe failure than a misconfigured deny-list rule. **Mitigation 1:** allow-list records SHOULD have `validUntil` set (signed consent has shelf life). **Mitigation 2:** the UI for adding an allow-list rule requires a `consentDocument` upload (signed PDF) and a `consentMethod` value. **Mitigation 3:** logging — every allow-list match is logged with rule UUID + matched entity for audit.

### Cache invalidation lag in multi-worker deployments

Adding a rule must be reflected in detection within seconds. Pure in-process caches don't fan out across workers. **Mitigation:** rely on OpenRegister's existing object-changed event bus + a TTL-bounded fallback (cache rebuild every N seconds) so a worker that missed the event recovers within a bounded window. Acceptable for v1; if it proves problematic, upgrade to explicit pub/sub.

### Polymorphic reference complexity for downstream consumers

Frontend / external consumers of `publicationConsent` may not expect `policyMatch` to be either of two object types. **Mitigation:** the `consentStatus` enum value disambiguates. Consumers that don't recognize the new statuses can treat them as terminal (no further workflow possible) — still valid.

### "Reference" match type deferred

Deferring `reference` (link to a Person/Org object in BRP/KvK register) means we can't say "this BSN-keyed person in BRP is protected" with one rule — admins must duplicate the BSN into the deny list. **Mitigation:** acceptable for v1. Reference-based matching introduces cross-register lookup latency and stale-cache concerns that deserve their own design pass.

### Conflict resolution gives implicit precedence to the most recent deny rule

If two deny-list rules match (e.g. one by exact name, one by BSN), the audit trail might lose information about which rule matched first. **Mitigation:** the matching service records ALL matched rule UUIDs in the audit log, not just the one that wins. The `policyMatch` field on `publicationConsent` records the first deny-list match (deterministic order: rule UUID ascending). This is consistent with the deny-list-as-policy-set semantics — multiple deny rules saying "always anonymize" is reinforcing, not conflicting.

## Migration Plan

No data migration needed. Roll-out:

1. Land schema changes in `lib/Settings/docudesk_register.json`. New schemas come up empty. Existing `publicationConsent` records remain valid (additive changes).
2. Detection service starts consulting empty deny/allow caches — initially every detection falls through to existing WOO workflow. Behavior is unchanged.
3. Privileged users populate the deny list with known protected entities. Each addition triggers cache reload + retroactive force-resolve of in-flight records.
4. (Later) Privileged users populate the allow list with prior-consent entities. Each addition leaves in-flight records alone; future detections benefit.
5. Frontend toggle behavior ships in the same release as the schema changes; users see the new locked/defaulted states immediately.

No flag/gate needed — the absence of policy records is functionally equivalent to "no policy active." The change is backwards-compatible from the moment it lands.

## Seed Data

Per ADR-016. Both new schemas need 3-5 realistic seed objects. The seed data is realistic across organization types (municipality, consultancy, travel agency) and demonstrates the variety of legitimate uses.

### Seed for `mandatoryAnonymization` (4 records)

```json
[
  {
    "@self": { "register": "consent", "schema": "mandatoryAnonymization", "slug": "court-order-2024-0312" },
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
    "@self": { "register": "consent", "schema": "mandatoryAnonymization", "slug": "minor-protection-stichting-jeugd" },
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
    "@self": { "register": "consent", "schema": "mandatoryAnonymization", "slug": "undercover-officer-jansen" },
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
    "@self": { "register": "consent", "schema": "mandatoryAnonymization", "slug": "privacy-board-categorial-minors" },
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

### Seed for `publicationAllowance` (4 records)

```json
[
  {
    "@self": { "register": "consent", "schema": "publicationAllowance", "slug": "mayor-de-vries-blanket-consent" },
    "primaryName": "Burgemeester De Vries",
    "entityType": "PERSON",
    "matchRules": [
      { "type": "exact", "value": "Burgemeester De Vries" },
      { "type": "exact", "value": "Mevrouw De Vries" },
      { "type": "exact", "value": "Karin de Vries" }
    ],
    "reason": "Publieke functie — staande publicatietoestemming voor handelingen in officiële capaciteit.",
    "consentDocument": null,
    "consentMethod": "opt_in_form",
    "consentScope": "Alleen documenten betreffende uitoefening van de burgemeestersfunctie. Privé-aangelegenheden vallen buiten de toestemming.",
    "addedBy": null,
    "validFrom": "2024-09-01T00:00:00+00:00",
    "validUntil": "2028-09-01T00:00:00+00:00",
    "active": true,
    "notes": "Termijn loopt parallel aan de termijn als burgemeester."
  },
  {
    "@self": { "register": "consent", "schema": "publicationAllowance", "slug": "kvk-zaak-en-partner-bv" },
    "primaryName": "Zaak & Partner B.V.",
    "entityType": "ORGANIZATION",
    "matchRules": [
      { "type": "kvk", "value": "87654321" },
      { "type": "exact", "value": "Zaak & Partner B.V." },
      { "type": "exact", "value": "Zaak en Partner" }
    ],
    "reason": "Vennootschap heeft als adviseur voor het publicatieprogramma getekend voor opname in publieke documenten zonder per-document notificatie.",
    "consentDocument": null,
    "consentMethod": "digital_signature",
    "consentScope": "Documenten in het kader van het programma 'Open Bestuur 2025-2028'.",
    "addedBy": null,
    "validFrom": "2025-01-15T00:00:00+00:00",
    "validUntil": "2028-12-31T00:00:00+00:00",
    "active": true,
    "notes": null
  },
  {
    "@self": { "register": "consent", "schema": "publicationAllowance", "slug": "councilmember-bakker" },
    "primaryName": "Raadslid Bakker (gemeente Voorbeeld)",
    "entityType": "PERSON",
    "matchRules": [
      { "type": "exact", "value": "S. Bakker" },
      { "type": "exact", "value": "Sylvia Bakker" },
      { "type": "bsn", "value": "999888777" }
    ],
    "reason": "Raadslid in actieve functie — opt-in toestemming voor publicatie in raadsverslagen, moties, amendementen.",
    "consentDocument": null,
    "consentMethod": "paper",
    "consentScope": "Uitsluitend documenten gerelateerd aan raadswerkzaamheden in gemeente Voorbeeld.",
    "addedBy": null,
    "validFrom": "2024-03-15T00:00:00+00:00",
    "validUntil": "2026-03-15T00:00:00+00:00",
    "active": true,
    "notes": "Termijn herzien bij start nieuwe raadsperiode."
  },
  {
    "@self": { "register": "consent", "schema": "publicationAllowance", "slug": "directeur-jansen-reisorg" },
    "primaryName": "Directeur M. Jansen",
    "entityType": "PERSON",
    "matchRules": [
      { "type": "exact", "value": "Maartje Jansen" },
      { "type": "exact", "value": "M. Jansen (directeur)" }
    ],
    "reason": "Directeur van reisorganisatie heeft schriftelijke toestemming gegeven voor publicatie in jaarverslagen en pers-uitingen van moederorganisatie.",
    "consentDocument": null,
    "consentMethod": "verbal_recorded",
    "consentScope": "Jaarverslagen en pers-uitingen — geen interne stukken.",
    "addedBy": null,
    "validFrom": "2025-10-01T00:00:00+00:00",
    "validUntil": "2026-10-01T00:00:00+00:00",
    "active": true,
    "notes": "Termijn beperkt tot 1 jaar; herzien bij volgende jaargesprek."
  }
]
```

The `consentDocument` field is left `null` in seed data because the file references can't be created at seed-import time (no actual signed PDFs to attach). Production records would populate this with a real file UUID.

`addedBy` is `null` in seed — the import process attributes ownership to the system user.
