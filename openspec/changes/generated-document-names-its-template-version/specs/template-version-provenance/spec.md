# template-version-provenance Specification (delta)

---
status: proposed
---

## Purpose

A document filinq generated names the template version it was rendered from,
a caller can pin that version so the document can be produced again, and
filinq can answer which version was in force on a given date. Today the audit
record says version 1 for every document ever generated, because the number is
read off a property the `template` schema does not declare.

## ADDED Requirements

### Requirement: A template carries the version it is on (REQ-DDTVP-001)

`TemplateService::getTemplate()` MUST return the template's OpenRegister
object version at the top level of the returned array. OpenRegister carries
that version under `@self.version`; the `template` schema declares no
`version` property of its own, so a top-level read returns null and any
caller that coalesces it to a default reports that default forever.
`getTemplate()` MUST NOT invent a version when OpenRegister supplies none: it
MUST omit the key so a caller can tell "unversioned" from "version 1".

#### Scenario: The version a caller reads is the version OpenRegister holds

- **GIVEN** a template whose OpenRegister object is at version 4
- **WHEN** a caller reads it through `TemplateService::getTemplate()`
- **THEN** the returned array reports version 4 at the top level
- **AND** the `@self` block still carries the same value
- @e2e exclude Service-level read with no UI surface; covered by PHPUnit.

#### Scenario: An unversioned template says so

- **GIVEN** a template whose OpenRegister object carries no version
- **WHEN** a caller reads it through `TemplateService::getTemplate()`
- **THEN** the version key is absent rather than set to 1
- @e2e exclude Service-level read with no UI surface; covered by PHPUnit.

### Requirement: A generated document records the version that produced it (REQ-DDTVP-002)

`DocumentService::generateDocument()` MUST record the template version it
actually rendered on the `generatedDocument` audit record, read from the
template object rather than defaulted. The generation result MUST report the
same version to the caller. A render whose template version cannot be
established MUST record that it could not, and MUST NOT record 1.

#### Scenario: The audit record names the rendered version

- **GIVEN** a template at version 4
- **WHEN** a caseworker generates a document from it
- **THEN** the `generatedDocument` record names version 4
- **AND** the generation result reports version 4
- @e2e exclude Backend generation path; covered by PHPUnit and the API test.

#### Scenario: An unknown version is not reported as 1

- **GIVEN** a template whose version cannot be established
- **WHEN** a caseworker generates a document from it
- **THEN** the record says the version is unknown
- **AND** no numeric version is written
- @e2e exclude Backend generation path; covered by PHPUnit.

### Requirement: A caller can pin the template version to render (REQ-DDTVP-003)

`DocumentService::generateDocument()` MUST accept an optional
`options.templateVersion`. When present, it MUST render the stored content of
that version through the existing pipeline, resolved through
`TemplateVersionService`, and MUST NOT render the head. When the named version
does not exist, generation MUST fail with a message naming the template and
the version, and MUST NOT fall back to the head. When absent, generation MUST
render the head exactly as it does today.

#### Scenario: A beschikking is regenerated from the version that made it

- **GIVEN** a document generated from template version 2
- **AND** the template has since moved to version 5
- **WHEN** a caseworker regenerates it pinned to version 2
- **THEN** the output is rendered from version 2's stored content
- @e2e exclude Backend generation path; covered by PHPUnit.

#### Scenario: A version that does not exist fails rather than falling back

- **GIVEN** a template with versions 1 to 3
- **WHEN** a caller asks for version 9
- **THEN** generation fails with a message naming the template and version 9
- **AND** no document is produced or stored
- @e2e exclude Backend generation path; covered by PHPUnit.

### Requirement: Filinq answers which version was in force on a date (REQ-DDTVP-004)

`TemplateVersionService` MUST answer, for a template and a moment, which
version was current at that moment, resolved from the creation timestamps of
the version chain it already stores. When the moment predates the first
stored version, it MUST say so rather than returning the oldest version.
Filinq MUST NOT echo the requested date back as though it had selected on it.

#### Scenario: A date inside the chain selects the version in force

- **GIVEN** version 2 created on 1 March and version 3 created on 1 June
- **WHEN** a caller asks which version was in force on 1 April
- **THEN** filinq answers version 2
- @e2e exclude Service-level query with no UI surface; covered by PHPUnit.

#### Scenario: A date before the chain begins is refused

- **GIVEN** the earliest stored version was created on 1 March
- **WHEN** a caller asks which version was in force on 1 January
- **THEN** filinq answers that no version was in force
- **AND** does not return the earliest version
- @e2e exclude Service-level query with no UI surface; covered by PHPUnit.

### Requirement: The generation result reports the bytes filinq produced (REQ-DDTVP-005)

The result of `DocumentService::generateDocument()` MUST report a SHA-256 over
the produced bytes and, for a paginated format, the page count. Filinq holds
the bytes at that point, so every caller that needs either currently derives
it again from the file. A format with no page structure MUST report no page
count rather than zero.

#### Scenario: A caller reads the checksum instead of hashing the file again

- **GIVEN** a caseworker generates a PDF
- **WHEN** the result comes back
- **THEN** it carries a SHA-256 over the produced bytes and the page count
- @e2e exclude Backend generation path; covered by PHPUnit.

#### Scenario: A format with no pages reports no page count

- **GIVEN** a caseworker generates an HTML output
- **WHEN** the result comes back
- **THEN** it carries no page count rather than a page count of zero
- @e2e exclude Backend generation path; covered by PHPUnit.
