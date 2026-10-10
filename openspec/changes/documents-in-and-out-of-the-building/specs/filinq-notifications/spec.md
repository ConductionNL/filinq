# filinq-notifications Specification (delta)

---
status: proposed
---

## Purpose delta

A download of a file filinq owns is recorded and the declared watchers
are told while it is happening, through the verified notification engine
dialect and its staff-only routing. Parity register row 13.39, rating
`no`, entered under decision D1.

## ADDED Requirements

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
