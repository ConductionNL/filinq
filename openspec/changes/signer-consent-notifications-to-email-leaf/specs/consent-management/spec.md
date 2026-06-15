# consent-management Specification (delta)

---
status: proposed
---

## Purpose

Route the consent-management notification system (CONS-011) through the OR email integration
leaf's comms surface rather than a bespoke notifier. References the email leaf
(`integration-email`), ADR-019, and ADR-022.

## ADDED Requirements

### Requirement: Consent Notification Routes Through the Email Leaf

The consent objection-period notification SHALL be delivered via NC Mail and linked to the
consent OR object through the email integration leaf, so it appears on the object's comms
surface. The `notificationStatus` transitions (`pending → sent → delivered/failed`, plus
`skipped`) SHALL be driven by the linked message's lifecycle rather than by a private notifier
state table. The CONS-011 transition contract SHALL be preserved; only its source of truth moves
to the leaf.

#### Scenario: Consent notification linked via the email leaf

- GIVEN a consent record with `notificationStatus='pending'` and a resolvable contact channel
- WHEN the objection-period notification is sent
- THEN the message SHALL be linked to the consent object via the email leaf's link-table
- AND `notificationStatus` SHALL transition to `sent`
- AND the notification SHALL appear on the consent/document detail comms surface (`CnEmailTab`)

#### Scenario: Delivery and failure are driven by the linked message

- GIVEN a consent notification linked via the email leaf with `notificationStatus='sent'`
- WHEN the linked message reports a delivery signal
- THEN `notificationStatus` SHALL transition to `delivered`
- AND on a failure signal it SHALL transition to `failed`
- AND no private notifier state table SHALL be the source of truth for these transitions

#### Scenario: No channel results in skipped

- GIVEN a consent record with no resolvable contact channel
- WHEN notification is attempted
- THEN `notificationStatus` SHALL be set to `skipped`
- AND no email-leaf link SHALL be created
