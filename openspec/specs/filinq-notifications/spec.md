---
status: done
---

# filinq-notifications Specification

## Purpose
Defines the notification rules for Filinq's signing, document, consent, and correspondence schemas, declared in OpenRegister's verified `x-openregister-notifications` dialect. Rules route to confirmed Nextcloud user IDs, groups, and object-ACL recipients only, deliberately never notifying external data-subject email addresses, and approximate status-transition triggers where the engine cannot yet express field-change conditions. This keeps Filinq's automated notifications staff-safe and aligned with the supported notification engine.

@e2e exclude Backend schema notification-rule declarations (x-openregister-notifications dialect) with no browser surface. Covered by PHPUnit schema-validation tests and the OR notification-engine integration suite.

## Requirements
### Requirement: Signing and document schemas MUST declare notifications in the verified engine dialect

`signerRecord`, `signingRequest`, `publicationConsent`, and `correspondence` MUST declare `x-openregister-notifications` using only verified keys: `trigger.type`, `channels[]`, `recipients[]`, and inline `subject{nl,en}`.

#### Scenario: Signer receives a created-trigger notification

- **GIVEN** a `signerRecord` is created per signer when a signing request fans out
- **WHEN** the signingRequested rule is declared
- **THEN** it uses `trigger.type: "created"` with `recipients: [{"kind": "field", "field": "userId"}]`
- **AND** `userId` is a confirmed Nextcloud user ID

### Requirement: Data-subject (external email) recipients MUST NOT be used; staff-only routing for consent rules

`publicationConsent.contactEmail` is an external email string, not a uid. No rule MUST route to it. The objection-deadline rule MUST notify staff only (`kind:groups` Woo/records officers and/or `kind:object-acl` manage).

#### Scenario: Objection deadline notifies staff, never the data subject

- **GIVEN** `publicationConsent.contactEmail` holds an external email
- **WHEN** the objectionDeadline rule is declared
- **THEN** its `recipients` are `kind:groups` and `kind:object-acl` only
- **AND** no recipient references `contactEmail`

### Requirement: Only-when-status forms MUST be approximated and deferrals documented

Where the engine cannot express "fire only when status = failed/COMPLETED" (a field-change pattern), rules MUST be approximated via `created` or `scheduled`+`filter`, and the precise form documented as deferred to `notification-updated-field-change-condition` or a named transition action.

#### Scenario: Correspondence failure approximated by created

- **GIVEN** `correspondence.status` is `generated` or `failed` and no `filter` on `created` is confirmed
- **WHEN** the correspondenceFailed rule is declared
- **THEN** it uses `trigger.type: "created"` routed to `field:generatedBy` and a staff group
- **AND** the proposal's Caveats note the only-when-failed form is deferred

