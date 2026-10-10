# woo-publicatie-pipeline Specification (delta)

---
status: proposed
---

## Purpose

filinq writes the DiWoo document type to opencatalogi's own field and never
into the summary. Decision D8, paired with
`opencatalogi/diwoo-metadata-on-the-publication`. Supports Woo rows 2.3 and
2.25.

## MODIFIED Requirements

### Requirement: Handoff to OpenCatalogi as the publication endpoint (REQ-DDWPP-005)

On handoff of a `ready` record, the app MUST create (or update, when
`endpointPublicationRef` already exists) an OpenRegister object addressed to
OpenCatalogi's register slug `publication`, schema slug `publication`,
mapping `officieleTitel` to `title`, `documentsoort` to `documentsoort` and
`publicatiedatum` to `publicationDate` (the fields OpenCatalogi's
`publication` schema declares; the field map is pinned against a copy of it
by a unit test), and attach the redacted derivative (`redactedFileRef`),
NEVER the original file. The app MUST NOT write a document type into
`summary`. Before the write, the app MUST check that OpenCatalogi's
`publication` schema declares `documentsoort`; when it does not, the handoff
MUST be refused with a message naming the OpenCatalogi update it needs, and
the record MUST stay `ready`. It MUST store `endpointPublicationRef` and
`handoffAt`, transition to `handed_off` and append a `handed_off` log entry
only after the write succeeded. Filinq MUST NOT render any public
portal, sitemap or search surface. When OpenCatalogi is not installed, handoff
MUST be disabled with an explanatory state, never a silent no-op. An OR
authorization failure, or OpenCatalogi refusing the document type, on the
endpoint write MUST surface to the operator and leave the record `ready`.

#### Scenario: Successful handoff creates the endpoint publication

- GIVEN a `ready` record with complete DiWoo metadata, `documentsoort` `besluit` and a redacted derivative
- WHEN the operator confirms handoff
- THEN an OpenCatalogi `publication` object exists with `documentsoort` holding the besluit member and an empty `summary`, with the redacted file attached
- AND the record shows `handed_off` with `endpointPublicationRef` set and a `handed_off` log entry
- @e2e tests/e2e/workflows/woo-publicatie-pipeline.spec.ts

#### Scenario: The document type never reaches the summary

- GIVEN any record with a `documentsoort`
- WHEN `OpenCatalogiPublicationMap::toPublication()` maps it
- THEN the result has `documentsoort` and has no `summary` key
- @e2e exclude a field map; covered by PHPUnit `OpenCatalogiPublicationMapTest::testTheDocumentTypeGoesToItsOwnField`

#### Scenario: An opencatalogi without the field refuses the handoff

- GIVEN an installed opencatalogi whose `publication` schema does not declare `documentsoort`
- WHEN the operator confirms handoff
- THEN the handoff is refused naming the opencatalogi update it needs, no publication is written, and the record stays `ready`
- @e2e exclude needs an older opencatalogi; covered by PHPUnit `PublicationPipelineServiceTest::testAnOpenCatalogiWithoutTheFieldRefusesTheHandoff`

#### Scenario: A document type outside the national list is refused

- GIVEN a record with `documentsoort` `memo van de wethouder`
- WHEN the handoff writes and OpenCatalogi refuses the value
- THEN the operator sees the refusal naming `documentsoort`, and the record stays `ready`
- @e2e exclude a cross-app refusal; covered by PHPUnit `PublicationPipelineServiceTest::testARefusedDocumentTypeLeavesTheRecordReady`

#### Scenario: Endpoint absent

- GIVEN an instance without OpenCatalogi
- WHEN the operator views a `ready` record
- THEN the handoff action is disabled with an explanation that OpenCatalogi provides the publication endpoint
- @e2e tests/e2e/spec-coverage/woo-publications.spec.ts
