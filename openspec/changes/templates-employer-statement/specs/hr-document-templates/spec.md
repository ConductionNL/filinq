# hr-document-templates Specification (delta)

## Purpose

filinq ships the standard HR document templates humaniq looks up by
namespace and category, starting with the employer statement. Matrix row
`fil-employer-statement` (humaniq matrix, owned by filinq).

## ADDED Requirements

### Requirement: filinq ships one standard employer statement humaniq can find (REQ-TES-001)

The register MUST seed exactly one `template` object with namespace
`hrmq` and category `werkgeversverklaring`, slug
`werkgeversverklaring-standaard`, at `@self.version` 1.0.0, so that
`TemplateService::getTemplatesByNamespace('hrmq')` returns exactly one
template in that category on a fresh install.

Rows: `fil-employer-statement` (humaniq matrix)

#### Scenario: humaniq finds the template on a fresh install

- GIVEN a fresh install with filinq and humaniq and no template id configured in humaniq
- WHEN an HR officer runs `occ humaniq:documents:generate --type werkgeversverklaring` for employee Jansen
- THEN the generated document record in humaniq has status `generated` and a PDF file, not the "Geen docudesk-sjabloon gevonden" refusal
- @e2e exclude started from occ today; the page action is humaniq's half, covered by PHPUnit on the seed plus a humaniq integration test

### Requirement: The statement renders from what humaniq sends (REQ-TES-002)

The template MUST render in filinq's Twig sandbox from the `Employee` and
`EmploymentContract` data references and the `employer` and `document`
ad-hoc blocks humaniq sends. It MUST read the items humaniq does not hold
from an optional `statement` block and MUST print "niet opgegeven" for a
missing item instead of failing. It MUST NOT merge a BSN.

Rows: `fil-employer-statement` (humaniq matrix)

#### Scenario: A statement for a permanent contract

- GIVEN employee Jansen with a permanent contract from 2024-03-01 at 36 hours and a gross monthly salary of 3800
- WHEN the template is rendered with humaniq's data and no `statement` block
- THEN the PDF shows the employer's name and KvK number, Jansen's name and date of birth, "vast" as contract type, 36 hours, 3.800,00 per month, and "niet opgegeven" for holiday allowance
- AND the PDF contains no BSN
- @e2e exclude a rendering contract between two apps; covered by PHPUnit rendering the seeded content with fixture data in `TemplateRenderer`

#### Scenario: A fixed-term contract with an intention to extend

- GIVEN a fixed-term contract ending 2027-02-28 and a `statement` block with `extensionIntended` true
- WHEN the template is rendered
- THEN the PDF shows the end date and the line that the employer intends to extend the contract
- @e2e exclude same as above; covered by PHPUnit with a second fixture

### Requirement: An organisation's edit survives an upgrade (REQ-TES-003)

A change to the seeded template's wording made on an install MUST survive
a filinq upgrade that does not raise the seed's `@self.version`. The docs
MUST say to edit the seeded template, or to set humaniq's template id,
and MUST warn that a duplicate in the same namespace and category makes
humaniq refuse.

Rows: `fil-employer-statement` (humaniq matrix)

#### Scenario: An admin adapts the wording and upgrades

- GIVEN an admin who changed the closing sentence of "Werkgeversverklaring (standaard)" on `/templates/<id>`
- WHEN filinq is upgraded and the register is imported again
- THEN the template still has the admin's closing sentence
- @e2e exclude an upgrade path; covered by a repair run in the integration job asserting the object is unchanged
