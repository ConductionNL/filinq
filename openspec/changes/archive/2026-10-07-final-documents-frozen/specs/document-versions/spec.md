# document-versions Specification (delta)

---
status: proposed
---

## Purpose

A document declared final stops changing, and a correction becomes a new
version rather than a rewrite. Round 4 discovery candidate C-documents-5,
`must`, a matrix hole, three driven passers. The document half of cluster
29; openregister keeps the case, the phase and the message.

## ADDED Requirements

### Requirement: Final is a declared lifecycle state (REQ-FDF-01)

A document version MUST carry a declared lifecycle with `draft` and a
terminal `final` state. Reaching `final` MUST record who made it final,
when, and the reason or the act that did so, and MUST record the file's
checksum at that moment. `final` MUST have no transition out.

#### Scenario: A besluit becomes final

- GIVEN a draft besluit
- WHEN it is declared final
- THEN its version is `final`, naming the person, the moment, the reason and the file's checksum

#### Scenario: There is no way back

- GIVEN a final version
- WHEN a transition to `draft` is attempted through the objects API
- THEN it is refused
- @e2e exclude lifecycle enforcement; covered by PHPUnit against the objects API

### Requirement: Every write path refuses a final version (REQ-FDF-02)

Filinq MUST refuse content changes, meaning-changing metadata changes and
file replacement on a final version, in the service layer every write
path resolves through. The refusal MUST name the version, its state, who
made it final and when. The refusal MUST hold for the API, both editors,
the batch correspondence path, the merge and the anonymisation output.

#### Scenario: The editor refuses, and explains

- GIVEN a final besluit
- WHEN a handler opens it in the editor and tries to save a change
- THEN the save is refused, naming who made it final and when

#### Scenario: The API refuses too

- GIVEN the same document
- WHEN a caller replaces the file through the API
- THEN the request is refused with the same reason
- @e2e exclude multi-path enforcement; covered by PHPUnit across every named write path

#### Scenario: A file changed outside the product is reported

- GIVEN a final version whose file is changed on the storage
- WHEN the checksum is next verified
- THEN the mismatch is reported against the version

### Requirement: A correction supersedes, it does not overwrite (REQ-FDF-03)

Correcting a final document MUST create a new version referencing the one
it supersedes. The superseded version MUST stay readable and MUST stay
`final`. A reader of either version MUST be able to see the chain.

#### Scenario: A corrected besluit keeps its predecessor

- GIVEN a final besluit with an error
- WHEN a handler issues a correction
- THEN a new version exists referencing the old one, and the old one is still readable and still final

### Requirement: The consuming app declares what makes a document final (REQ-FDF-04)

Filinq MUST store, per record type reference a consuming app declares,
which states make that type's documents final, and MUST apply it when the
state changes. Filinq MUST NOT define the record type vocabulary itself.

#### Scenario: The decision freezes the besluit

- GIVEN a case type declaring that the "besluit genomen" status makes its besluit final
- WHEN the case reaches that status
- THEN the besluit's current version becomes final, naming the transition as the reason

#### Scenario: No declaration, no automatic freeze

- GIVEN a record type with no declaration
- WHEN its state changes
- THEN no document is frozen automatically

### Requirement: An administrator may unfreeze, and it is recorded forever (REQ-FDF-05)

An administrator MUST be able to unfreeze a final version. Doing so MUST
write an audit entry naming the person, the moment and the reason, and
MUST mark the document permanently as having been unfrozen. The mark MUST
NOT be removable.

#### Scenario: An unfreeze leaves a scar

- GIVEN a final document an administrator unfreezes with a reason
- WHEN anybody opens it afterwards
- THEN the document shows that it was unfrozen, by whom, when and why

#### Scenario: The mark cannot be cleared

- GIVEN an unfrozen document
- WHEN an administrator attempts to remove the mark
- THEN the attempt is refused
- @e2e exclude immutability of the mark; covered by PHPUnit on the service
