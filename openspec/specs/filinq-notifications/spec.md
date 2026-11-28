---
status: done
---

# filinq-notifications Specification

## Purpose
Defines the notification rules for Filinq's signing, document, consent, and correspondence schemas, declared in OpenRegister's verified `x-openregister-notifications` dialect. Rules route to confirmed Nextcloud user IDs, groups, and object-ACL recipients only, deliberately never notifying external data-subject email addresses, and approximate status-transition triggers where the engine cannot yet express field-change conditions. This keeps Filinq's automated notifications staff-safe and aligned with the supported notification engine.

@e2e exclude Backend schema notification-rule declarations (x-openregister-notifications dialect) with no browser surface. Covered by PHPUnit schema-validation tests and the OR notification-engine integration suite.

A download of a file filinq owns is recorded and the declared watchers
are told while it is happening, through the verified notification engine
dialect and its staff-only routing. Parity register row 13.39, rating
`no`, entered under decision D1.

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

### Requirement: A failed reading notifies the records officers (REQ-IFR-01)

`intakeDocument` MUST declare `readingState`, `readingError` and
`readingUpdatedAt`, with `readingState` facetable and carrying `failed`
in its enum, and MUST declare a `readingFailed` rule in the verified
`x-openregister-notifications` dialect whose trigger filters on
`readingState: failed`.

The rule MUST address a named group through `kind: groups`, never an
external address and never an empty recipient list. The descriptor
version MUST be bumped, or `SettingsInitializer` never imports the rule.

#### Scenario: A jammed scanner produces a message

- GIVEN a document whose reading failed
- WHEN the scheduled rule next runs
- THEN it matches on `readingState: failed`
- AND it addresses the records officers group

#### Scenario: The filter names a property that exists

- GIVEN the rule filters on `readingState`
- WHEN the descriptor is read
- THEN `readingState` is a declared property of `intakeDocument`
- AND `failed` is one of its values

### Requirement: A storm is one message, not hundreds (REQ-IFR-02)

The rule MUST declare a coalescing window and a daily digest, and the
digest MUST name a timezone. The message MUST say that the documents are
still in the inbox and still assignable by hand.

#### Scenario: Four hundred failures at two in the morning

- GIVEN a scanner that jams and fails every page it holds
- WHEN the rule fires
- THEN the failures inside the window are folded into one message
- AND the rest arrive as one digest at the declared local time

#### Scenario: The registrar can still work

- GIVEN a registrar reading the message
- WHEN they open the inbox
- THEN the message has already told them the documents are there and can be handled by hand

### Requirement: The inbox says when the notification reaches nobody (REQ-IFR-03)

The inbox MUST be able to say how many people the rule reaches, and MUST
warn, naming the group, when that number is zero. The warning MUST appear
even when nothing has failed yet, and when something has failed it MUST
say that the reader is seeing it because they opened the inbox and not
because anybody was notified.

A fallback recipient MUST NOT be substituted for a named group.

#### Scenario: The group is empty and nothing has failed

- GIVEN nobody is in the notified group
- WHEN a registrar opens the inbox
- THEN they are told that a failure tonight would reach nobody
- AND the warning names the group to add a person to

#### Scenario: The group is empty and documents failed

- GIVEN documents that could not be read and an empty notified group
- WHEN a registrar opens the inbox
- THEN they are told how many failed, that nobody was told, and why they are seeing it

### Requirement: A download is recorded with its route and its identity (REQ-DIO-06)

Filinq MUST record every download of a file it owns, naming the file, the
version, the moment, the route (`ui`, `api`, `public-link` or `portal`)
and the person who downloaded it. Where the route carries no session
user, the record MUST name the link and the person who created it and
MUST leave the downloader unidentified rather than attributing the
download to anybody. The recording MUST NOT render or deliver a
notification on the download path; it MUST enqueue that work, per
ADR-078.

#### Scenario: A handler downloads a document

- GIVEN a document filinq owns
- WHEN a handler downloads it from the web interface
- THEN a download record names the file, the version, the moment, the route `ui` and the handler

#### Scenario: A public link download names the link, not a person

- GIVEN a file shared by public link
- WHEN somebody downloads it through that link
- THEN the record names the link and its creator, the route is `public-link`, and no downloader identity is written

#### Scenario: Every route is recorded

- GIVEN the same file
- WHEN it is downloaded through the API, through a sync client and through the portal
- THEN each download is recorded with its own route
- @e2e exclude non-browser routes; covered by PHPUnit and Newman per route

#### Scenario: The download does not wait for the notification

- GIVEN a document with watchers declared
- WHEN it is downloaded
- THEN the file is served and the notification is enqueued rather than delivered on the download path

### Requirement: Who is told is declared on the schema, and is staff (REQ-DIO-07)

The download notification MUST be declared in the
`x-openregister-notifications` dialect on the schema rather than written
as a notification service. Recipients MUST be the document's steward and
whoever declared a watch on the document or its record. A data subject
MUST NOT be a recipient of this notification by external email, which is
what this capability already requires of every filinq notification.
Repeated downloads of the same document by the same person inside a
declared window MUST collapse to one notification.

#### Scenario: The steward is told

- GIVEN a document whose steward is declared
- WHEN somebody else downloads it
- THEN the steward is notified, naming the document, the person and the moment

#### Scenario: A watcher is told, and nobody else

- GIVEN a case one jurist watches and two colleagues do not
- WHEN a document on that case is downloaded
- THEN only the jurist and the steward are notified

#### Scenario: No external email to a data subject

- GIVEN a schema declaring a data-subject email recipient on the download notification
- WHEN the register is imported
- THEN the declaration is refused, in the same way this capability already refuses it elsewhere
- @e2e exclude register import validation; covered by PHPUnit on the register descriptor

#### Scenario: Ten clicks are one notification

- GIVEN a person downloading the same document ten times inside the declared window
- WHEN the notifications are delivered
- THEN one notification is sent, naming the person, the document and the number of downloads
