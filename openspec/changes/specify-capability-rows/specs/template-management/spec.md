# template-management Specification (delta)

## Purpose

Trying a template with sample data before users can pick it.

## ADDED Requirements

### Requirement: A template is tried with sample data before users get it (REQ-SCR-015)

A new or changed template version MUST start as a draft. The author MUST be able to preview a draft with sample data they enter or pick from a record. Users who generate documents MUST only be offered published versions, and publishing MUST be an explicit action of the author.

Rows: `tpl-test` (filinq matrix)

#### Scenario: A draft is not offered to users

- GIVEN a template whose new version is a draft
- WHEN a handler picks the template to generate a letter
- THEN the letter is rendered from the last published version
- @e2e exclude specification only; the change that builds it adds the Playwright test

#### Scenario: The author previews with sample data

- GIVEN the same draft
- WHEN the author fills in sample data and chooses preview
- THEN the preview shows the draft filled with that data
- @e2e exclude specification only; the change that builds it adds the Playwright test

### Requirement: Only the editors of a template change it (REQ-SCR-019)

The template schema MUST say who may use a template and who may change it. Every signed-in user MAY read a template and generate a document from it. Only members of the template editors group MAY change or delete a template, and OpenRegister MUST refuse the change for anyone else before it is written.

Rows: `tpl-access` (filinq matrix)

#### Scenario: A handler generates from a template but cannot change it

- GIVEN a handler who is not in the template editors group
- WHEN they generate a letter from the template "Ontvangstbevestiging bezwaar"
- THEN the letter is generated
- AND when they save a change to that template, the save is refused and the template is unchanged
- @e2e exclude enforced by OpenRegister's schema authorisation; covered by the authorisation-coverage PHPUnit test on the register

#### Scenario: A template editor changes the template

- GIVEN a member of the template editors group
- WHEN they save a change to the same template
- THEN the template holds the change and a new version is kept
- @e2e exclude enforced by OpenRegister's schema authorisation; covered by the authorisation-coverage PHPUnit test on the register
