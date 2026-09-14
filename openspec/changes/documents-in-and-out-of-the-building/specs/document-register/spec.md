# document-register Specification (delta)

---
status: proposed
---

## Purpose delta

A document that arrives or leaves is registered: a direction, a
registration date, a unit, and a number from a sequence scoped to that
unit and that year. An outbound registration discharges the inbound one
it answers, and a number that never became a registration is accounted
for. Parity register row 4.26, rating `no`, entered under decision D1.

## ADDED Requirements

### Requirement: A registered document carries a direction, a unit and a number (REQ-DIO-01)

Filinq MUST offer a registration entry for a document, carrying the
direction (`incoming`, `outgoing` or `internal`), the registration date,
the organisational unit, the document it registers, and a registration
number. The number MUST be rendered by OpenRegister's generated
identifier annotation from a sequence named for the unit, with the year
in the format and a yearly reset. Filinq MUST NOT implement a counter of
its own. The number MUST be refused on update once it is set.

#### Scenario: The first outgoing letter of the year

- GIVEN a unit with no registrations in the current year
- WHEN a handler registers an outgoing besluit for that unit
- THEN the entry carries direction `outgoing`, the unit, today's date and the first number of the year for that unit

#### Scenario: Two units do not share a counter

- GIVEN two units each registering a document on the same day
- WHEN both registrations are written
- THEN each carries the first number of its own sequence, and neither number appears in the other unit's series

#### Scenario: The number is set once

- GIVEN a registration entry with a number
- WHEN a caller updates the entry with a different number through the objects API
- THEN the update is refused and the stored number is unchanged
- @e2e exclude write guard on the objects API; covered by PHPUnit

#### Scenario: A new year starts a new series

- GIVEN a unit whose last registration was in the previous year
- WHEN the first document of the new year is registered
- THEN its number carries the new year and the sequence position starts again

#### Scenario: Registration refuses rather than numbering by hand

- GIVEN an instance whose register declares no generated identifier for the registration number
- WHEN a document is registered
- THEN the registration is refused, naming the missing sequence, and no entry is written
- @e2e exclude configuration failure path; covered by PHPUnit with an unannotated schema

### Requirement: An outbound registration discharges the inbound one it answers (REQ-DIO-02)

An outbound registration MUST be able to name an inbound registration it
answers. The inbound entry MUST then read as discharged, carrying the
date of the first discharge and every outbound number that answered it.
The discharge MUST be read from the link rather than written as a status
on the inbound entry. Filinq MUST offer the undischarged inbound entries
of a unit as a list, and MUST offer that list as a leaf per ADR-066.

#### Scenario: A reply discharges the request

- GIVEN a registered incoming aanvraag
- WHEN the outgoing besluit is registered naming that inbound registration
- THEN the inbound entry reads as discharged on today's date, naming the outbound number

#### Scenario: Two replies to one request

- GIVEN an inbound entry already discharged by a herstelverzoek
- WHEN the decision is registered against the same inbound entry
- THEN both outbound numbers are listed against it and the discharge date is still the first one

#### Scenario: What is still open

- GIVEN a unit with inbound entries, some discharged and some not
- WHEN the open post list for that unit is read
- THEN it holds exactly the undischarged entries, oldest first

### Requirement: A number that never became a registration is accounted for (REQ-DIO-03)

When a sequence value is allocated and the registration it was allocated
for is not written, filinq MUST record the allocated number as withdrawn,
with the reason and the moment. A reader of a unit's series MUST find
every number in it, and a number carrying no document MUST say why. The
spec does not claim the sequence itself never skips: OpenRegister's
generator is gap tolerant and never reuses a value after a rollback.

#### Scenario: A failed registration leaves an explanation, not a hole

- GIVEN a registration that takes a number and then fails on validation
- WHEN the unit's series is read
- THEN the allocated number is present as withdrawn, naming the reason and the moment

#### Scenario: The series reads end to end

- GIVEN a unit with registrations and one withdrawn allocation
- WHEN the series is read from the first number to the last
- THEN no number is absent, and each is either a registration or a withdrawal with a reason

#### Scenario: A withdrawal is not reused

- GIVEN a withdrawn number
- WHEN the next document of that unit is registered
- THEN it takes the following number and never the withdrawn one
- @e2e exclude sequence behaviour; covered by PHPUnit against the generated identifier annotation
