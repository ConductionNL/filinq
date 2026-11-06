# letter-correspondence-generation Specification (delta)

---
status: proposed
---

## Purpose delta

A generated letter may carry a plain-language rendition beside the formal
one, made from the same data in the same generation, naming the formal
document it explains and carrying the statements the template declares as
required. Parity register row 4.28, rating `no`, entered under decision
D1.

## ADDED Requirements

### Requirement: A template may declare a plain-language counterpart (REQ-DIO-04)

A template MUST be able to declare a plain-language counterpart template
and the statements the plain rendition MUST resolve, the decision, the
term and where to object among them. Generating from such a template MUST
produce both renditions from the same data, in one generation, bound to
the same generated-document record. The plain rendition MUST name the
formal document it explains, on the document itself. The formal rendition
MUST remain the legal text and MUST NOT be altered by this change. A
template with no counterpart MUST generate the formal rendition only.

#### Scenario: A besluit leaves with both renditions

- GIVEN a besluit template declaring a plain-language counterpart
- WHEN a handler generates the letter
- THEN both renditions exist, from the same data and the same generation record, and the plain one names the formal document

#### Scenario: The term is in the plain rendition

- GIVEN a template declaring the bezwaartermijn as a required statement
- WHEN the letter is generated
- THEN the plain rendition carries the term, the date it ends and where to object

#### Scenario: No counterpart, no invention

- GIVEN a template with no plain-language counterpart declared
- WHEN the letter is generated
- THEN only the formal rendition is produced and nothing is generated in its place

#### Scenario: A missing required statement stops the generation

- GIVEN a plain-language counterpart whose required statement cannot be resolved from the data
- WHEN the letter is generated
- THEN the generation is refused, naming the unresolved statement, and neither rendition is filed
- @e2e exclude generation failure path; covered by PHPUnit on the generation service

#### Scenario: A correction takes the twin with it

- GIVEN a besluit with both renditions
- WHEN the formal letter is regenerated after a correction
- THEN the plain rendition is regenerated in the same act, and a stale plain rendition is never left beside a corrected formal one

### Requirement: A machine-assisted rewrite is a suggestion a person accepts (REQ-DIO-05)

Where the plain rendition is drafted with machine assistance, filinq MUST
offer it as a suggestion and MUST NOT file it until a named person has
accepted it. The acceptance MUST record the person and the moment. An
unaccepted suggestion MUST NOT leave the building through any generation,
correspondence or portal path.

#### Scenario: The draft waits for a person

- GIVEN a machine-drafted plain rendition
- WHEN the letter is sent before anybody has accepted the draft
- THEN the send is refused and the draft is still waiting

#### Scenario: Acceptance is recorded

- GIVEN a handler who accepts the draft
- WHEN the letter is generated
- THEN the plain rendition is filed and the record names who accepted it and when

#### Scenario: The refusal holds off the screen

- GIVEN the same unaccepted draft
- WHEN it is requested through the correspondence API
- THEN the request is refused with the same reason
- @e2e exclude API enforcement; covered by PHPUnit across the generation and correspondence paths
