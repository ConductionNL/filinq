---
status: draft
---

# Docudesk Schema Notifications

## Purpose

Adds `x-openregister-notifications` annotations to `lib/Settings/docudesk_register.json` in the verified OpenRegister notification-engine dialect, covering signing-request-to-signers, signing deadline, consent objection-deadline (staff only), and correspondence-failed. Configuration-only; no data-model or API change.

## ADDED Requirements

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
