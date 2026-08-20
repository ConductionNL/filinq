# document-register Specification (delta)

---
status: proposed
---

## Purpose delta

The document register hosts the `mdtoSupplement` schema carrying the MDTO
informatieobject attributes OpenRegister's platform metadata does not
model, one supplement per described document/dossier record.

## ADDED Requirements

### Requirement: MdtoSupplement Schema in Document Register (REQ-DDTMM-020)

The document register MUST include the `mdtoSupplement` schema with full
`required`, `properties` and `hardValidation: true` (OR Adoption
Decision 3): `objectRef`, `objectType` (`document` | `dossier`),
`aggregatieniveau` (`archiefstuk` | `dossier`), `omschrijving`, `taal`,
`dekkingInTijdBegin`, `dekkingInTijdEind`, `beperkingGebruik[]`,
`betrokkene[]`, `archiefvormerOverride`. The schema MUST NOT duplicate any
retention-owned core attribute (waardering, bewaartermijn,
archiefactiedatum, archiefstatus, classificatie), and MUST NOT carry the
`x-openregister-archival` auto-delete annotation: a supplement describes a
record and MUST live exactly as long as the record it describes (it is
removed with its record through the vernietigingslijst path, never by an
independent sweep). The register version MUST be bumped for boot import.

#### Scenario: Schema present after version bump

- GIVEN a fresh installation after `ConfigurationService::importFromApp()` runs
- WHEN the document register's schemas are listed via `objectService->getSchemas(register: 'document')`
- THEN `mdtoSupplement` is included with `hardValidation: true`
- @e2e exclude boot-time register import with no UI surface — covered by PHPUnit register-import assertions (tests/unit/Settings/)

#### Scenario: Supplement carries no retention-owned attribute

- GIVEN the shipped `mdtoSupplement` schema definition
- WHEN its properties are checked against the retention-owned core attribute names
- THEN none of waardering/bewaartermijn/archiefactiedatum/archiefstatus/classificatie appears as a schema property
- AND the schema declares no `x-openregister-archival` annotation
- @e2e exclude declarative register-content rule — covered by a PHPUnit register-lint test
