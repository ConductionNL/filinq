# Employer statement (werkgeversverklaring)

An employee needs an employer statement for a mortgage or a rental home. humaniq asks Filinq to render one, and Filinq ships the template it renders: **Werkgeversverklaring (standaard)**, in namespace `hrmq`, category `werkgeversverklaring`.

## How humaniq finds it

humaniq looks for templates in namespace `hrmq` whose category is `werkgeversverklaring`. It needs exactly one:

- none found: it refuses to generate;
- two or more found: it refuses too, and names them.

An organisation that keeps its own variant can instead set humaniq's template id, which humaniq reads first.

## What it merges

| From | Fields |
|---|---|
| `employer` (sent by humaniq) | name, address, KvK number, city, the signatory's name and title |
| `Employee` | first and last name, date of birth, employee number, start of employment, gross monthly salary |
| `EmploymentContract` | type (permanent, temporary, agency, minijob, bbl, printed in Dutch), start, end, hours per week |
| `document` | the request date, printed as the date of signing |

The gross annual salary is the monthly salary times twelve. Holiday allowance is a separate line, so it is not hidden in that figure.

The statement never prints a BSN.

## The `statement` block

humaniq does not hold everything a lender asks for. These items come from the `statement` block of the request:

- `holidayAllowance`
- `endOfYearPayment`
- `extensionIntended`
- `wageGarnishment`
- `remarks`

Any item that is missing is printed as **niet opgegeven**, so a missing value never breaks the statement and never looks like a filled-in one. A missing date is never printed as today.

## Changing the wording

Edit the seeded template in place on the Templates page. Filinq ships it at version 1.0.0 and does not overwrite a template you changed, unless a later release says it replaces local edits.

Do not duplicate it to make your own version. The copy keeps namespace and category, so humaniq finds two templates and refuses to generate. If you need a second variant, give it another category, or set humaniq's template id to the one you want.
