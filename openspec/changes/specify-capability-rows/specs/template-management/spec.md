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
