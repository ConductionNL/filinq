## ADDED Requirements

### Requirement: A render names its engine (REQ-TEV-001)

`DocumentService::generateDocument()` SHALL read `options.engine`. Absent or
`filinq`, it SHALL render as it does without the option. `vendor:<sourceSlug>`
SHALL render through the integriq document generation source of that slug.
Any other value SHALL be refused with HTTP 400 naming the value.

#### Scenario: no engine renders as before
- **GIVEN** a published template
- **WHEN** a document is generated without `options.engine`
- **THEN** filinq renders it with its own engine, exactly as before
- @e2e exclude engine dispatch; covered by PHPUnit

#### Scenario: an unknown engine is refused
- **WHEN** a document is generated with `options.engine` `docmosis`
- **THEN** the call is refused with 400 naming `docmosis`
- @e2e exclude refusal path; covered by PHPUnit

### Requirement: A vendor render goes to integriq and never falls back (REQ-TEV-002)

A `vendor:<sourceSlug>` render SHALL dispatch integriq's
`DocumentRenderRequestedEvent` for that source with the template's
`vendorTemplateId`, the merge data, the acting user and `requestedByApp`
`filinq`, and SHALL record a `generatedDocument` in state `pending` carrying
the job id integriq answered. When integriq is not installed, the slug names
no source, the template has no `vendorTemplateId`, or the event comes back
refused or unanswered, the render SHALL be refused with a message naming the
source. A vendor render SHALL NOT fall back to filinq's own engine.

#### Scenario: a render reaches SmartDocuments
- **GIVEN** an active integriq source `smartdocuments` and a template with a `vendorTemplateId`
- **WHEN** a document is generated with engine `vendor:smartdocuments`
- **THEN** integriq receives the render request and a `pending` entry holds its job id
- @e2e exclude cross-app typed events; covered by PHPUnit with a stubbed event answer

#### Scenario: integriq refuses
- **GIVEN** integriq answers the request with a refusal
- **WHEN** the render runs
- **THEN** the call is refused naming the source, and nothing is rendered by filinq's engine
- @e2e exclude refusal path; covered by PHPUnit

### Requirement: The vendor's file is stored like a filinq render (REQ-TEV-003)

On integriq's `DocumentRenderedEvent` for a job filinq requested, filinq
SHALL store the vendor's file where it stores its own output for that
request and mark the `generatedDocument` `generated`, or `failed` with the
vendor's error.

#### Scenario: a rendered job becomes the document
- **GIVEN** a `pending` entry for job J
- **WHEN** integriq dispatches `DocumentRenderedEvent` for J with a file
- **THEN** the file is stored and the entry is `generated` with its file id
- @e2e exclude cross-app typed events; covered by PHPUnit

### Requirement: The engines an instance offers are listed (REQ-TEV-004)

`GET /api/templates/engines` SHALL answer `filinq` and one `vendor:<slug>`
per active integriq document generation source, with its display name; with
integriq absent it SHALL answer `filinq` alone.

#### Scenario: without integriq only filinq is offered
- **GIVEN** integriq is not installed
- **WHEN** the engines are listed
- **THEN** the answer is `filinq` alone
- @e2e exclude depends on app presence; covered by PHPUnit
