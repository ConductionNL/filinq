# Every capability row names its requirement

## Why

The filinq coverage audit of 30 October 2026 found 30 capability rows in
`openspec/parity/capabilities.json` that have no requirement, or only a
spec whose requirements do not name the row. Two rows (self-hosting and
the open licence) have no spec at all. Three built rows describe behaviour
no requirement states: the template editor in the browser, the metadata a
document type must carry, and the scanner profile per source.

## What changes

- New capability `platform-properties`: filinq runs on the customer's own
  Nextcloud with no outside service required, and its source is public
  under EUPL-1.2.
- `template-management` gains the browser editor requirement (REQ-TMPL-14).
- `document-register` gains the requirement that each document type
  declares its required metadata as a register schema (REQ-DREG-06).
- The open change `scan-intake-with-separator-sheets` gains REQ-SCI-05
  (a saved scanner profile per source) and a task to show the profiles on
  the intake channels page, as the board `filinq/FqInvoerkanalen` draws it.
- The open change `document-waarmerk-certification` gains REQ-DDWMK-006
  (one sealed evidence summary per completed signing transaction) and the
  tasks to build it.
- Every one of the 30 rows records `built.spec` and `built.requirement`.

## Impact

Specs and row data only. No code changes. The first three requirements
describe behaviour that is on `development` today; the two open-change
requirements come with build tasks.
