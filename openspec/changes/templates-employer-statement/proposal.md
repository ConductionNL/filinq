---
kind: config
depends_on: []
---

# Proposal: templates-employer-statement

Matrix row `fil-employer-statement` from humaniq's matrix
(`openspec/parity/capabilities.json` in ConductionNL/humaniq), rated
partial there and owned by filinq. Written in the OpenSpec pass of 27
September 2026.

## Why

An employee needs an employer statement (werkgeversverklaring) for a
mortgage or a rental home. humaniq can ask filinq to render one, and filinq
has nothing to render it with.

humaniq looks for the template itself. `HrDocumentService::selectTemplate()`
(humaniq `lib/Service/HrDocumentService.php:1030`) reads a configured
template id first, then asks filinq for every template in namespace `hrmq`
(`getTemplatesByNamespace()`, :1037) and keeps the ones whose `category`
equals the document type. On zero matches it refuses: 'Geen
docudesk-sjabloon gevonden voor "werkgeversverklaring" in namespace
"hrmq"; genereren is geweigerd.' On several it also refuses, because it
never guesses between templates that produce official documents.

filinq ships three templates, all in namespace `filinq`
(`lib/Settings/filinq_register.json`, seed objects `beschikking-standaard`,
`brief-algemeen`, `rapportage-kwartaal`). `grep -rniE 'hrmq|werkgeversverklaring'
lib src` finds nothing. So every employer statement humaniq starts ends
refused. humaniq's own demo shows the dead end from the other side: seed
`gendoc-werkgeversverklaring-jansen-skipped`
(humaniq `lib/Settings/register.d/hr-documents.json:224`) is a statement
that was never produced.

### Matrix rows (humaniq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `fil-employer-statement` | Produce an employer statement for an employee's mortgage or rental application. | partial: humaniq has an occ trigger (`lib/Command/DocumentsGenerateCommand.php:59,92`) and a viewer, filinq renders, but filinq ships no template humaniq can find and no humaniq page can request one |

The row comes from humaniq's matrix and is owned by filinq. The missing
half this change specifies: the standard employer statement template that
humaniq discovers in namespace `hrmq`, category `werkgeversverklaring`,
where today every request is refused for want of a template.

### Competitors rated yes

- AFAS: "the workflow Werkgeversverklaring lets an employee request an
  employer statement, which the manager assesses and payroll checks, signs
  and registers, ending with a digitally signed statement". Evidence:
  https://help.afas.nl/content/NL/SE/134864.htm
- Visma Raet: "employees request a Werkgeversverklaring through a Self
  Service form". Evidence:
  https://www.ssc-ons.nl/content/uploads/2024/07/Handleiding-Mijn-Youforce-1.pdf
- HR2day: "process buttons for requesting an employer statement
  (werkgeversverklaring)" and "document category Werkgeversverklaring".
  Evidence: https://data.maglr.com/1697/issues/41191/512818/index.html and
  https://data.maglr.com/1697/issues/45076/553517/index.html

## What changes

- One seeded template, `werkgeversverklaring-standaard`, in namespace
  `hrmq` with category `werkgeversverklaring`, so humaniq's discovery finds
  exactly one.
- It merges what humaniq already sends: the employer block (`employer.name`,
  `address`, `kvkNumber`, `loonheffingennummer`), the `Employee` and the
  `EmploymentContract` it passes as data references, and the request date.
- The items a lender asks for that humaniq does not hold (holiday
  allowance, end-of-year payment, an intention to extend a fixed-term
  contract, wage garnishment) are read from an optional `statement` block.
  A missing item prints as "not stated". It never breaks the render.
- No BSN is merged. The statement names the employee by name and date of
  birth (AVG art. 5(1)(c), data minimisation).
- A section in the docs says how an organisation adapts the wording
  without breaking humaniq's discovery.

## Capabilities

### New capabilities

- `hr-document-templates`: standard HR document templates filinq ships
  for humaniq to discover, starting with the employer statement.

### Modified capabilities

None.

## Impact

- `lib/Settings/filinq_register.json`: one seed object on `template`, and
  a register version bump so it reaches existing installs.
- `tests/unit/`: a render test of the seeded template in the Twig sandbox.
- `docs/features/`: a section with a screenshot of a rendered statement.

## Out of scope

- Signing the statement. humaniq can hand the rendered file to filinq's
  signing flow as it does for offer letters; that is humaniq's call.
- The other HR letters humaniq names (`arbeidsovereenkomst`,
  `aanbiedingsbrief`, `getuigschrift`). Each is its own template and its
  own row.
- The lender's own forms. Some lenders require their own statement form;
  this template is the organisation's statement.

## Cross-app dependencies

- humaniq: a page action "Request employer statement" on the employee
  page and a request flow per employee (the employee asks, HR fills in the
  items humaniq does not hold and approves), which calls
  `generateDocument()` with the `statement` block in `adHocData`. Today
  only `occ humaniq:documents:generate --type werkgeversverklaring` can
  start one.
- humaniq: keep `TEMPLATE_NAMESPACE = 'hrmq'` (humaniq
  `HrDocumentService.php:128`) or move this seed with it. The namespace is
  a stored value both sides match on.
