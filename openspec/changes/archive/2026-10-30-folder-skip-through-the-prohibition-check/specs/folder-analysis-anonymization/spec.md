# folder-analysis-anonymization (delta)

## ADDED Requirements

### Requirement: Folder decisions pass the publication prohibition check

A skip or legal-basis decision made on the folder anonymisation screen MUST be
saved through filinq's relation route, so the same prohibition check applies
as on the single-file screen. The browser MUST NOT write an entity relation
directly to OpenRegister.

#### Scenario: skipping a prohibited person from the folder screen
- GIVEN a detected person who matches a publish-never rule below the high-confidence threshold
- WHEN a user marks that person as skipped on the folder screen and starts the anonymisation
- THEN the decision is sent to `/apps/filinq/api/anonymization/relations/{id}`
- AND the refusal is shown on that person's row
- AND the person stays included
