# Design: templates-employer-statement

Kind: config. One seed object and a render test. No filinq code changes.

## Context

Read at filinq development `2088cc1f` and humaniq `77c1fd5`.

- filinq `template` schema (`lib/Settings/filinq_register.json:549`,
  version 1.3.0) requires `name`, `content` and `namespace`, and carries
  `category`, `format`, `orientation`, `tags` and `plainLanguage`.
- Three templates are seeded, all in namespace `filinq`. None is an HR
  document.
- `TemplateService::getTemplatesByNamespace()`
  (`lib/Service/TemplateService.php:358`) filters on `namespace` with a
  limit of 100.
- `TemplateService::duplicateTemplate()` copies `namespace` and `category`
  (:386-392). A duplicate of an HR template is a second match for humaniq.
- `DataResolverService::resolve()` (`lib/Service/DataResolverService.php:158`)
  keys each resolved data reference by its schema slug, and ad-hoc data
  overrides on top. humaniq sends `Employee` and `EmploymentContract`
  references and ad-hoc `employer` and `document` blocks
  (humaniq `HrDocumentService.php` `buildDataRefs()`, `buildOptions()`,
  :1084-1140).
- The Twig sandbox allows `if`, `for`, `set` and the filters `date`,
  `number_format` and `default` (`lib/Service/TemplateRenderer.php:57,114`).
- humaniq fields read at `lib/Settings/register.d/hr-objects.json`:
  `Employee.firstName`, `lastName`, `dateOfBirth`, `startDate`,
  `grossMonthlySalary`, `employeeNumber`; `EmploymentContract.type`
  (`permanent`, `temporary`, `agency`, `minijob`, `bbl`), `startDate`,
  `endDate`, `hoursPerWeek`.
- OpenRegister updates an existing seed object only when the shipped
  `@self.version` is higher (`ro-openregister
  lib/Service/Configuration/ImportHandler.php:3175-3196`).

New: the seed object `werkgeversverklaring-standaard`.

## Goals / Non-goals

Goals: humaniq finds exactly one employer statement template; it renders
from the data humaniq already sends; a missing item never breaks it.

Non-goals: a request page, signing, lender-specific forms.

## Decisions

### D1. Found by namespace and category, not by id

The seed sets `namespace: hrmq` and `category: werkgeversverklaring`,
which is the lookup humaniq already does. Alternative considered: humaniq
configures the template id. Rejected as the default, because the id is
different on every install; it stays humaniq's override for an
organisation that keeps its own variant.

### D2. Merge only what humaniq sends, plus an optional statement block

The template reads `employer.*`, `Employee.*`, `EmploymentContract.*` and
`document.requestedAt`. The items humaniq does not hold come from
`statement.holidayAllowance`, `statement.endOfYearPayment`,
`statement.extensionIntended`, `statement.wageGarnishment` and
`statement.remarks`, each printed with `default('not stated')` in Dutch
("niet opgegeven"). Alternative considered: ask humaniq to add these
fields to its schemas first. Rejected for this change: the template is
useful today and the block is the contract humaniq's request form fills.

### D3. The seed is edited in place, and upgrades keep the edit

The seed ships at `@self.version` 1.0.0. OpenRegister leaves an existing
object alone unless the shipped version is higher, so an organisation that
adapts the wording keeps it across upgrades. A later wording fix is shipped
as a higher version only with a release note that it replaces local edits.
Alternative considered: tell organisations to duplicate. Rejected, because
a duplicate is a second match and humaniq then refuses (D1, `:386-392`).

### D4. No BSN, and the statement is Dutch

The template merges no BSN (AVG art. 5(1)(c)). The text is Dutch, because
the statement goes to Dutch lenders and landlords. The template name and
description get an English form through the template's translatable
fields.

## Seed data

One `template` object:

| field | value |
|---|---|
| slug | `werkgeversverklaring-standaard` |
| name | Werkgeversverklaring (standaard) |
| namespace | `hrmq` |
| category | `werkgeversverklaring` |
| format, orientation | A4, P |
| tags | werkgeversverklaring, hr, hypotheek, huur |
| content | employer block; employee name, date of birth, employee number, start of employment; contract type, start, end, hours per week; gross monthly salary and the same times twelve; the `statement` items; place, date and a signature line for the employer |

## Risks / trade-offs

- A clerk duplicates the template to adapt it. humaniq then finds two and
  refuses, and its message names both. The docs say to edit the seeded one
  or to set humaniq's template id.
- humaniq's app id moves from `hrmq` to `humaniq`. The namespace here is a
  stored value, not an app id check; it moves only if humaniq's
  `TEMPLATE_NAMESPACE` moves, in one coordinated change.
- A gross annual salary of twelve times the monthly salary leaves out
  holiday allowance. The statement shows holiday allowance as its own line.

## Open questions

- Should `duplicateTemplate()` clear `category` on a copy in another app's
  namespace, so a copy is never a second match? That is a code change and
  is left to a later change.
- Should humaniq add the function title (its `normfunctieId` points at a
  `Normfunctie`)? The template prints it when the reference resolves.
