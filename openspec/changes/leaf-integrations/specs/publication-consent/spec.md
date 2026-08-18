# publication-consent Specification (delta)

---
status: proposed
---

## Purpose

Surface the standard OR leaves on the consent record: NC Mail linkage plus a
create-from-email template, the calendar leaf for the Woo objection window
(`objectionDeadline`), and the deck leaf for publication follow-ups. Consumes
`integration-email`, `integration-calendar`, and `integration-deck` via the registry
(ADR-019, ADR-022); consent semantics and statuses are unchanged.

## ADDED Requirements

### Requirement: Consent Records Are A Mail Link Target With Create-From-Email

The `publicationConsent` schema SHALL declare `configuration.linkedTypes` containing
`"mail"` and a `configuration.mailObjectTemplate` field map, so that from the NC Mail
sidebar a caseworker can (a) link an objection or consent email to the existing consent
record it concerns, and (b) create a new consent record from an email, with the message's
sender address pre-filling `contactEmail` and the subject pre-filling `notes`. Every key
in the `mailObjectTemplate` map SHALL name a real `publicationConsent` property (OR's
`Schema` configuration validation rejects the import otherwise). Creation from email
SHALL produce a record in the normal initial consent state; it SHALL NOT set a
`publicationDecision` or advance `consentStatus` by itself.

#### Scenario: Objection email becomes a consent record

- GIVEN an inbound objection email in NC Mail from "burger@example.org"
- WHEN the caseworker uses the create-from-email action targeting `publicationConsent`
- THEN a new consent record SHALL be created with `contactEmail` pre-filled from the sender
- AND the record SHALL carry the normal initial status, with no publication decision taken

#### Scenario: Objection email linked to an existing record

- GIVEN an existing consent record and a follow-up email about the same entity
- WHEN the caseworker links the message from the Mail sidebar
- THEN the message SHALL appear on the consent record's leaf surface
- AND `consentStatus`, `objectionDeadline`, and `publicationDecision` SHALL be unchanged

### Requirement: The Objection Window Surfaces On The Calendar Leaf

The `publicationConsent` schema SHALL declare `configuration.linkedTypes` containing
`"calendar"`, so the calendar leaf renders on the consent record and the record's
`objectionDeadline` — the Woo four-week objection window — is visible as its temporal
anchor. The consent object SHALL remain the canonical store of the deadline; the leaf
SHALL NOT introduce a second write path for it, and passing the deadline SHALL NOT be
acted on by the leaf (decision flows stay in-app).

#### Scenario: The objection deadline is visible on the record

- GIVEN a consent record with `objectionDeadline` four weeks after notification
- WHEN the caseworker opens the consent record
- THEN the calendar leaf SHALL be rendered with the objection deadline visible

#### Scenario: No deadline, no entry

- GIVEN a consent record without an `objectionDeadline`
- WHEN its record surface renders
- THEN the calendar leaf SHALL render without an entry and SHALL NOT error

### Requirement: Publication Follow-Ups Use The Deck Leaf

The `publicationConsent` schema SHALL declare `configuration.linkedTypes` containing
`"deck"`, so publication follow-up work (send notification, await objection window,
publish or withhold) can be tracked as Deck cards linked to the consent record through
the leaf, instead of an app-local task widget. Deck cards SHALL be follow-up tracking
only: completing or moving a card SHALL NOT change `consentStatus` or
`publicationDecision`.

#### Scenario: A follow-up card is created from the consent record

- GIVEN a consent record whose objection window is running
- WHEN the caseworker creates a "publish after 2026-09-12" card via the deck leaf
- THEN the card SHALL be linked to the consent record and visible on its leaf surface
- AND the consent record's own status fields SHALL be unchanged

#### Scenario: Deck absent

- GIVEN a host instance without the Deck app
- WHEN the consent record surface renders
- THEN the deck leaf SHALL NOT be present and the page SHALL render without error

### Requirement: Consent Leaves Do Not Widen The Agent Surface

Enabling mail, calendar, and deck leaves on `publicationConsent` SHALL NOT expose the
schema through MCP: the `docudesk-mcp-adoption` exclusion of `publicationConsent`
(citizen contact details and objection reasons) SHALL continue to hold, and no leaf
SHALL be reachable through an agent tool.

#### Scenario: Agent still cannot enumerate consent records

- GIVEN the leaves of this delta enabled on `publicationConsent`
- WHEN an agent enumerates DocuDesk's MCP tool surface
- THEN no tool exposes `publicationConsent`
