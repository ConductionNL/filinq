## Context

The consent management module was first implemented in sprint 2026-03-21. It covers the full consent lifecycle required by GDPR/AVG and the Wet open overheid (WOO): detection of affected entities in documents, notification tracking, objection handling (minimum 4-week objection period per WOO Art. 4.4), and publication decision-making. All records are stored as `PublicationConsent` objects in OpenRegister.

The current service topology is:

```
ConsentController
  ├── index()  → SettingsService.getObjectService() → ObjectService.searchObjects()  [read path]
  ├── show()   → SettingsService.getObjectService() → ObjectService.findObject()     [read path]
  ├── update() → ConsentService.updateConsentStatus()                                [write path]
  └── byDocument() → ConsentService.getConsentsByDocument()                          [write path]

ConsentService
  ├── createConsentRequest()  → private getObjectService() [DUPLICATES SettingsService pattern]
  ├── updateConsentStatus()   → private getObjectService() [DUPLICATES SettingsService pattern]
  └── getObjectionPeriodDays() → IAppConfig directly       [DUPLICATES SettingsService key read]

ObjectionDeadlineChecker
  └── checkObjectionDeadline() → private getObjectService() [DUPLICATES SettingsService pattern]
```

Three gaps identified in code review:

1. **Security gap** — all ObjectService calls pass `_rbac: false, _multitenancy: false`. Multi-tenant deployments expose all consent records to all authenticated users.
2. **Creation gap** — `createConsentRequest()` exists but there is no `POST /api/consents` endpoint. The frontend cannot create consent records.
3. **Duplication gap** — `getObjectService()` is implemented three times (SettingsService, ConsentService, ObjectionDeadlineChecker) with identical logic; the objection period config key `publication_objection_period_days` is read in two places (SettingsService, ConsentService).

## Goals / Non-Goals

**Goals:**

- Enable RBAC and multitenancy on all consent ObjectService calls.
- Expose `createConsentRequest()` via `POST /api/consents` (admin-only per ADR-003 common patterns).
- Remove all duplicated ObjectService resolution — delegate to `SettingsService::getObjectService()`.
- Remove config key duplication — delegate objection period reading to `SettingsService::getAllSettings()`.
- Canonicalise the full consent management feature in spec format for downstream change references.

**Non-Goals:**

- Automated consent creation from entity detection events (a separate future change).
- Email or postal notification delivery (a separate integration).
- UI flow for creating consents directly from the document view.
- Any schema changes to `PublicationConsent` (this change is behaviour/infrastructure only).

## Decisions

### D1. Flip `_rbac` and `_multitenancy` to `true` — do not remove the flags

Explicitly passing `true` is preferable to removing the flags and relying on OpenRegister defaults. Explicit values are self-documenting and resilient to future changes in OpenRegister's default behavior.

**Risk:** Existing single-tenant deployments where all users share a flat namespace may break if RBAC groups are not configured. Mitigated by a CHANGELOG "Behavior change" entry and upgrade documentation instructing operators to verify RBAC group configuration before upgrading.

### D2. `POST /api/consents` is admin-only and delegates directly to `ConsentService::createConsentRequest()`

The controller validates the request body (required: `documentId`, `entityType`, `entityText`) and delegates to the existing service method. No new service layer. Admin-only guard via `IGroupManager::isAdmin()` per ADR-003 authorization pattern.

**Alternative considered:** Allow document owners (non-admin) to create consent records. Rejected for this change — the security context of the RBAC bug (CONS-044) makes expanding access surface premature. Revisit when automated consent creation from entity detection lands.

### D3. ObjectService resolution centralises in `SettingsService`

`ConsentService` and `ObjectionDeadlineChecker` are both already injected with `SettingsService`. Replacing their private `getObjectService()` methods with a call to `SettingsService::getObjectService()` eliminates duplication without changing the observable behaviour. The same applies to `getObjectionPeriodDays()` — delegate to `SettingsService::getAllSettings()['publicationObjectionPeriodDays']` (the key that `SettingsService` already reads).

**Trade-off:** If `SettingsService` is ever refactored, all three services are affected in one place rather than requiring three separate updates. This is the desired outcome.

### D4. No schema migration

The `PublicationConsent` schema is unchanged. This change modifies call-site flags and service delegation only. Per ADR-011 schema standards, adding optional fields is non-breaking; this change adds none. Rollback: revert RBAC flags and ObjectService delegation.

## Risks / Trade-offs

**[RBAC flip is a breaking behavior change]** — Users in multi-tenant deployments who relied (knowingly or not) on the RBAC bypass will lose cross-tenant access. This is the intended outcome but must be communicated clearly.

**[No automated consent creation]** — The creation gap is partially closed by the new `POST /api/consents` endpoint, but consent records still cannot be automatically triggered from entity detection. This remains an explicit Non-Goal and is tracked as a future change.

## Migration Plan

1. Update `ConsentService` — remove private `getObjectService()`, inject `SettingsService`, delegate resolution and config reading.
2. Update `ObjectionDeadlineChecker` — same: remove private `getObjectService()`, delegate to `SettingsService`.
3. Update all ObjectService calls in `ConsentService`, `ConsentCrudService`, and `ConsentController` — pass `_rbac: true, _multitenancy: true`.
4. Add `POST /api/consents` route to `appinfo/routes.php` (specific route before the `{id}` wildcard, per ADR-003).
5. Implement `ConsentController::create()` — validate body, delegate to `ConsentService::createConsentRequest()`, return 201.
6. Update unit tests; run `composer check:strict`.

**Rollback:** Revert RBAC flags to `false`, revert route addition. No schema changes; no data migration.

## Seed Data

Example `PublicationConsent` objects for demos and development (Dutch municipality context). These illustrate the full status lifecycle across the three required entity types and all notification/consent state combinations.

**Consent 1 — Person, consent given**

| Field | Value |
|---|---|
| `documentId` | `woo-zaak-2025-001482` |
| `entityType` | `PERSON` |
| `entityText` | `Jan de Vries` |
| `entityKey` | `PERS-001` |
| `contactEmail` | `j.devries@example.nl` |
| `contactAddress` | `Keizersgracht 124, 1015 CW Amsterdam` |
| `notificationStatus` | `delivered` |
| `notificationSentAt` | `2025-11-15T09:30:00+01:00` |
| `consentStatus` | `consent_given` |
| `objectionDeadline` | `2025-12-15T23:59:59+01:00` |
| `publicationDecision` | `publish_with_consent` |
| `legalBasis` | `WOO artikel 4.4 — toestemming gegeven` |
| `notes` | `Betrokkene heeft telefonisch toestemming gegeven voor publicatie.` |

**Consent 2 — Organization, pending**

| Field | Value |
|---|---|
| `documentId` | `woo-zaak-2025-001482` |
| `entityType` | `ORGANIZATION` |
| `entityText` | `Bouw & Infra BV` |
| `entityKey` | `ORG-001` |
| `contactEmail` | `info@bouwinfra.nl` |
| `notificationStatus` | `sent` |
| `notificationSentAt` | `2025-11-15T09:35:00+01:00` |
| `consentStatus` | `pending` |
| `objectionDeadline` | `2025-12-15T23:59:59+01:00` |
| `publicationDecision` | `pending` |
| `legalBasis` | `WOO artikel 4.4` |

**Consent 3 — Person, notification failed, pending**

| Field | Value |
|---|---|
| `documentId` | `woo-zaak-2025-002890` |
| `entityType` | `PERSON` |
| `entityText` | `Fatima El-Amrani` |
| `entityKey` | `PERS-002` |
| `contactAddress` | `Bergweg 56, 3037 EG Rotterdam` |
| `notificationStatus` | `failed` |
| `notificationSentAt` | `2025-12-01T14:00:00+01:00` |
| `consentStatus` | `pending` |
| `objectionDeadline` | `2026-01-01T23:59:59+01:00` |
| `publicationDecision` | `pending` |
| `legalBasis` | `WOO artikel 4.4` |
| `notes` | `E-mail aflevering mislukt. Teruggevallen op postmelding.` |

**Consent 4 — Person, objection received, published anonymized**

| Field | Value |
|---|---|
| `documentId` | `woo-zaak-2025-003100` |
| `entityType` | `PERSON` |
| `entityText` | `Pieter Bakker` |
| `entityKey` | `PERS-003` |
| `contactEmail` | `p.bakker@amsterdam.nl` |
| `notificationStatus` | `delivered` |
| `notificationSentAt` | `2025-10-01T10:00:00+01:00` |
| `consentStatus` | `objection_received` |
| `objectionDeadline` | `2025-11-01T23:59:59+01:00` |
| `objectionReceivedAt` | `2025-10-20T16:45:00+01:00` |
| `objectionReason` | `De genoemde gegevens zijn niet correct en kunnen mijn professionele reputatie schaden.` |
| `publicationDecision` | `publish_anonymized` |
| `legalBasis` | `WOO artikel 4.4 — bezwaar ontvangen` |
| `notes` | `Bezwaar beoordeeld. Besluit: geanonimiseerd publiceren conform WOO.` |

**Consent 5 — Organization, no response, notification skipped**

| Field | Value |
|---|---|
| `documentId` | `woo-zaak-2025-003100` |
| `entityType` | `ORGANIZATION` |
| `entityText` | `Gemeente Amsterdam` |
| `entityKey` | `ORG-002` |
| `contactEmail` | `woo@amsterdam.nl` |
| `notificationStatus` | `skipped` |
| `consentStatus` | `no_response` |
| `objectionDeadline` | `2025-11-01T23:59:59+01:00` |
| `publicationDecision` | `publish_with_consent` |
| `legalBasis` | `WOO artikel 4.4 — termijn verstreken, geen bezwaar` |
| `notes` | `Overheidsorgaan: kennisgeving overgeslagen conform beleid. Termijn verstreken zonder bezwaar.` |

## Open Questions

- Should `POST /api/consents` eventually be available to non-admin users (e.g. document owners)? Currently admin-only per the security-first posture of the RBAC fix. Revisit when automated consent creation from entity detection lands.
- Should `ConsentController::index()` support pagination (`_page` + `_limit`) per ADR-002? Currently delegates to `searchObjects()` without pagination parameters. Low risk for now (consent records are bounded by document count) but worth addressing before large-scale deployments.
