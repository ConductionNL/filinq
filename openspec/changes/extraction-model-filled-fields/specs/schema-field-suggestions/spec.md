# schema-field-suggestions Specification (delta)

## Purpose

A model reads a file and suggests values for the fields a record schema
declares. A person accepts, changes or rejects each one; nothing is written
without that. Matrix rows `wk-ml-fields` (filinq) and `plt-ai-fill`
(shillinq, owned by filinq), demand
https://github.com/paperless-ngx/paperless-ngx/discussions/6932.

## ADDED Requirements

### Requirement: Only declared fields are filled (REQ-EMF-001)

Filinq MUST fill only the fields an active `fieldFillProfile` names for
the target register and schema. A request for a schema without an active
profile MUST answer 409 "No fields are declared for model filling on this
schema." Saving a profile that names a field with the `bsn` format MUST be
refused with "A BSN field cannot be filled by a model."

Rows: `wk-ml-fields` (filinq matrix), `plt-ai-fill` (shillinq matrix)

#### Scenario: An admin declares the fields of a subsidy application

- GIVEN an admin on the filinq admin settings with model filling switched on
- WHEN the admin adds a profile for schema "subsidieaanvraag" with the fields applicant, amount and start date
- THEN the profile is listed as active with those three fields
- @e2e tests/e2e/schema-field-suggestions.spec.ts

#### Scenario: A schema without a profile is refused

- GIVEN no profile for schema "zaak"
- WHEN a caller posts to `api/extraction/fields` for a file on a "zaak" record
- THEN the answer is 409 with "No fields are declared for model filling on this schema." and no model task ran
- @e2e exclude API contract; covered by a Newman request and PHPUnit asserting the task manager is not called

### Requirement: Every suggestion fits its field and says where it came from (REQ-EMF-002)

`POST api/extraction/fields` MUST read the file's text, send the declared
fields with their type, format, allowed values, title and description to
a Nextcloud task-processing `TextToText` task, and check every returned
value against the field's schema definition. A value that fails the check
MUST be dropped and counted. Each kept value MUST carry the text passage
it occurs in, or `foundInText: false`. The result MUST be saved as a
`fieldSuggestion` in status `suggested` and returned.

Rows: `wk-ml-fields` (filinq matrix), `plt-ai-fill` (shillinq matrix)

#### Scenario: A date in the wrong shape is dropped

- GIVEN a profile whose field `startDate` has format `date`
- WHEN the model answers `startDate` with "next spring"
- THEN the suggestion has no `startDate` and `droppedFields` is 1
- @e2e exclude model output is not reproducible in a browser run; covered by PHPUnit with a stubbed task manager

### Requirement: A person decides each field before anything is written (REQ-EMF-003)

Filinq MUST NOT write a suggested value to a record without a decision.
`POST api/extraction/fields/{id}/decide` MUST take per field `accept`,
`change` with a value, or `reject`, MUST write the accepted and changed
values to the record through OpenRegister as the session user with that
user's rights, and MUST record each decision beside the original
suggestion. A field the record already holds MUST keep its value unless
the clerk accepts the suggestion for it.

Rows: `wk-ml-fields` (filinq matrix), `plt-ai-fill` (shillinq matrix)

#### Scenario: A registrar fills a record from an intake document

- GIVEN a registrar on `/intake` with a document assigned to a subsidy application record, and an active profile for that schema
- WHEN the registrar chooses "Fill fields from document", accepts applicant and amount, and rejects the start date
- THEN the record shows the accepted applicant and amount, its start date is unchanged, and the suggestion records two accepts and one reject
- @e2e tests/e2e/schema-field-suggestions.spec.ts

#### Scenario: A value not in the text is flagged

- GIVEN a suggestion whose amount does not occur in the document text
- WHEN the dialog opens
- THEN the amount row says "Not found in the document text"
- @e2e tests/e2e/schema-field-suggestions.spec.ts

### Requirement: The feature is off until an admin switches it on (REQ-EMF-004)

Model filling MUST be off on a fresh install. The admin settings MUST show
the task-processing provider that would read the documents before the
switch is turned on. With the switch off, or with no `TextToText` provider,
the route MUST answer 503 "No text model is available on this server." and
the "Fill fields from document" action MUST NOT be shown.

Rows: `wk-ml-fields` (filinq matrix)

#### Scenario: A server without a model

- GIVEN a server with no task-processing provider for `TextToText`
- WHEN an admin opens the filinq admin settings
- THEN the model filling section says no text model is available and the switch cannot be turned on
- AND the intake worklist shows no "Fill fields from document" action
- @e2e tests/e2e/schema-field-suggestions.spec.ts
