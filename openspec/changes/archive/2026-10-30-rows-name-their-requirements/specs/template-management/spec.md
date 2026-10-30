# template-management Specification (delta)

## ADDED Requirements

### Requirement: A template is created and edited in the browser (REQ-TMPL-14)

Filinq MUST offer a template editor at `/templates/:id` where a template
editor creates and changes a template's content without leaving the
browser: a formatted editing area with bold, italic, underline, headings
and lists, and a toggle to edit the raw HTML. Saving MUST go through the
template update route (REQ-TMPL-02). While another user holds the
template's edit lock (REQ-TMPL-11), the editor MUST name that user and
MUST NOT save.

#### Scenario: An editor changes a template and saves it

- GIVEN a template editor opens a template
- WHEN they make a heading bold and save
- THEN the template's content holds the change and a new version is kept
- @e2e tests/e2e/workflows/template-detail.spec.ts

#### Scenario: A locked template cannot be saved

- GIVEN another user holds the template's edit lock
- WHEN an editor opens the template
- THEN the editor reads "Locked by" with that user's name and the save button is disabled
- @e2e exclude lock contention needs two sessions; covered by PHPUnit in `TemplatesControllerLockingTest`
