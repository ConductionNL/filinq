## Context

The existing `publicationConsent` workflow runs the WOO process per-(document, entity): notification → 28-day objection → decision. Two cases this misses: always-anonymise (court order, undercover officer, minor) and standing-consent (mayor in official capacity, signed opt-in). This change covers the always-anonymise enforcement layer. The standing-consent + polymorphic `policyMatch` reference are in the sibling change `publication-consent-policy-fields`.

## Goals / Non-Goals

**Goals:**

- `publicationProhibition` schema as a deny-list expressed positively as a prohibition.
- `PolicyMatchService` with `exact` / `normalized` / `bsn` / `kvk` match types, in-memory cache invalidated by OR object-changed events.
- Retroactive force-resolve on prohibition INSERT/activate: in-flight `scope: "document"` records for matching entities transition to `anonymized` + `policyMatch` populated.
- Three admin surfaces: Publication Prohibitions CRUD page; indicator on existing Consent Workflow rows; the standing-consents page is the sibling change's concern.
- UI toggle behaviour for prohibition matches: locked ON, disabled, with tooltip.

**Non-Goals:**

- Standing consent flow + `scope` discriminator on `publicationConsent` — sibling.
- `regex` / `reference` match types — v2.
- Approval workflow on rule additions — separate change.
- Sweep of already-published documents.

## Decisions

### D1. Schema modelling: prohibition as positive statement

Functionally a deny-list but semantically a prohibition — "the absence of permission asserted positively". Justifies a dedicated schema rather than a flag on `publicationConsent`.

### D2. Match types in v1

`exact` (literal), `normalized` (case + accent strip via `Transliterator`), `bsn` (Dutch citizen ID), `kvk` (Chamber of Commerce number). `regex` rejected for false-positive risk; `reference` rejected for v1 complexity. Both deferred.

### D3. Conflict resolution: prohibition wins

If an entity matches both a prohibition and a standing consent, prohibition wins, full stop. Audit log records both matches. No configuration knob.

### D4. Cache invalidation via OR object-changed events

`PolicyMatchService` subscribes; on `publicationProhibition` events, rebuild the prohibition portion of the cache. Future enhancement (when standing consents land): on `publicationConsent` events, filter by `scope` — `entity` events invalidate the standing-consent portion; `document` events do NOT.

### D5. Retroactive: prohibition force-resolves; standing consent doesn't (asymmetric)

On `publicationProhibition` INSERT/activate, force-resolve in-flight records (even `objection_received` records — privacy default is anonymise, which is what an objection would lead to anyway). On standing-consent INSERT (sibling change's concern), do NOT modify in-flight records — future detections benefit; past detections respect what was already decided. Asymmetry is documented inline.

### D6. RBAC: privileged-only writes

`publicationProhibition.authorization`: `read` open to authenticated consent group; `write` restricted to a privileged group (e.g. `docudesk-policy-admins`). Two-eyes / formal approval is out of scope; tracked as a follow-up.

### D7. UI toggle keyed on `policyMatch` referent type

For `policyMatch → publicationProhibition`: toggle ON, disabled, tooltip. For `policyMatch → publicationConsent` (scope=entity, sibling change): toggle OFF default, enabled, user may flip ON to anonymise anyway (override-up). For `policyMatch: null`: existing UX based on `consentStatus`.

## Risks / Trade-offs

- **Name-only match rules + identical names → false-positive prohibition** → UI warns loudly when only a name-based rule is added; encourages BSN/KvK as stable identifier.
- **Prohibition retroactively overrides objection_received** → privacy-safe (already heading to anonymise); audit preserves `notificationSentAt` + `objectionReceivedAt` even though `objectionDeadline` clears.
- **Cache size on very large prohibition lists** → expected tens to low hundreds per org. If a tenant exceeds, future enhancement: paginated lookup with bounded LRU.

## Migration Plan

1. Add `publicationProhibition` schema to `docudesk_register.json`.
2. Land `PolicyMatchService` + cache + invalidation listener.
3. Land retroactive handler for prohibition rule changes.
4. Build the Publication Prohibitions admin page + Consent Workflow indicator.
5. Ship 4 seed records.

**Rollback:** Remove the schema (existing records orphan); disable the retroactive listener; admin pages become broken links (acceptable for emergency rollback).

## Seed Data

Four realistic records per `docs/features/publication-consent-process.md` design: court order, minor protection, undercover officer, categorical privacy-board exemption. Stable slugs; `@self` envelope per ADR-013.

## Open Questions

- Should the retroactive handler be synchronous or queue-based? Provisional: synchronous for v1 (small in-flight set); future: enqueue if the count exceeds a threshold.
