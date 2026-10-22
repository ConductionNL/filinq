# filinq-notifications Specification (delta)

---
status: proposed
---

## Purpose

A document that could not be read reaches a person, and when it cannot,
the inbox says so. Closes the limit named in filinq#1133: a failure is
recorded where only somebody who opens the inbox will meet it.

## ADDED Requirements

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
