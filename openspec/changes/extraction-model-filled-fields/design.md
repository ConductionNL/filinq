# Design: extraction-model-filled-fields

Kind: code. Two schemas, one service, two routes, one dialog, and one
shared model call taken out of the financial extraction.

## Context

Read at development `2088cc1f`.

- `lib/Service/FinancialExtractionService.php`: `extractFinancial()`
  (:236) runs `resolveText()` (:985, embedded PDF text or Tesseract through
  `OcrService`), heuristics in `runExtraction()` (:430), then
  `applyAiEnhancement()` (:509). The model call is `resolveAiManager()`
  (:832, `OCP\TaskProcessing\IManager` with a fallback to the older
  `TextProcessing` manager) and `runAiTask()` (:869), which runs a
  `TextToText` task synchronously and returns the output. `buildPrompt()`
  (:907) asks for strict JSON with fixed keys. A model-filled value gets
  the fixed confidence `AI_FILL_CONFIDENCE` 0.55 (:175). Field keys are the
  constant `FIELD_DEFAULTS` (:84).
- `lib/Service/MetadataService.php:100` `enhanceTextMetadata()` fills
  `language`, `keywords`, `topic` by heuristics, skip-if-populated.
- `lib/Service/RegisterDiscoveryService.php:66` already takes
  OpenRegister's `SchemaMapper` by injection, so filinq can read another
  app's schema definition.
- OpenRegister validates the `bsn` format with `BsnFormat`
  (`ro-openregister lib/Service/Object/ValidateObject.php:2016`) on top of
  `opis/json-schema` (:53-55). filinq already requires `opis/json-schema`
  in `composer.json` and uses it nowhere in `lib/`.
- `intakeDocument` (`lib/Settings/filinq_register.json`) carries `file`
  and `assignedTo` (register, schema, id): the file and the record it was
  filed on. `src/views/intake/IntakeIndex.vue:62-80` renders the row
  actions "Assign" and "Reject"; dialogs live in `src/dialogs/`.
- `src/views/settings/Settings.vue:263-301` shows the Tesseract status and
  switch; there is no model setting.
- Precedent for "a consuming app declares, filinq applies":
  `intakeRoutingRule` and `documentFinalityRule` (`declaringApp`,
  `typeReference`).
- Open change `inbound-auto-classification` adds `classificationResult`
  for a document type and a correspondent, rule based, confirmed by a
  person. No record-schema fields.

New: `fieldFillProfile`, `fieldSuggestion`, `SchemaFieldFillService`,
`LocalTextModel`, `DocumentTextSource`, `FieldSuggestionDialog.vue`, two
routes.

## Goals / Non-goals

Goals: fill the declared fields of any record schema from a file; never
write without a person; keep the decisions; one model call in filinq.

Non-goals: free prompts, training, unattended filling, BSN fields.

## Decisions

### D1. A declaration names the fields, per schema

`fieldFillProfile` holds `declaringApp`, `register`, `schema`, `fields`
(property names) and `active`, the same shape as `intakeRoutingRule`. A
request for a schema with no active profile answers 409 "No fields are
declared for model filling on this schema." Alternative considered: fill
every property of the schema. Rejected: a model then guesses at status
fields, relations and computed values nobody asked it to touch.
Alternative two: an annotation inside the other app's schema. Rejected:
filinq cannot switch it off without editing that app's register.

### D2. The schema is the prompt and the check

For each declared field the service reads `type`, `format`, `enum`,
`title` and `description` from the schema through `SchemaMapper`, puts
them in the prompt, and checks each answer against the same property with
`opis/json-schema` and OpenRegister's custom formats. An answer that fails
is dropped and counted in `droppedFields`. A declared field with format
`bsn` makes the profile invalid on save. Alternative considered: trust the
model's JSON. Rejected: a date in the wrong shape would fail the record
save later, far from the cause.

### D3. Every value says where it came from

A value that occurs in the document text is stored with the passage around
it (at most 200 characters). A value that does not occur literally is
marked `foundInText: false`, and the dialog says "Not found in the
document text". Confidence is the fixed `AI_FILL_CONFIDENCE`, raised by
0.2 when the value is found in the text. Alternative considered: ask the
model for a confidence. Rejected: a model's self-reported confidence is
not a measurement.

### D4. A person decides each field; the write is theirs

`POST api/extraction/fields/{id}/decide` takes per field `accept`,
`change` (with the value) or `reject`, writes the accepted and changed
values to the record through `ObjectService` as the session user, and
records each decision with the original suggestion on the
`fieldSuggestion`. A field the record already holds is shown with its
current value and is not overwritten unless the clerk accepts. Alternative
considered: pre-fill the form and let the save be the acceptance.
Rejected: then nothing records which values a person checked (AI Act art.
14 human oversight).

### D5. One model call, local by configuration

`resolveAiManager()` and `runAiTask()` move into
`lib/Service/Ai/LocalTextModel.php`, used by the financial extraction and
this service; `resolveText()` moves into `DocumentTextSource`. The feature
is off by default. The settings page shows the provider Nextcloud task
processing reports for `TextToText` before the admin switches it on. With
no provider, the route answers 503 "No text model is available on this
server." and the row action is hidden. Alternative considered: call hermiq
directly. Rejected: hermiq is optional, and task processing is the
Nextcloud seam the financial extraction already uses.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| which fields may be filled | `fieldFillProfile` objects | a declaration, not code |
| reading, prompting, checking | imperative service | a model call and validation against another app's schema |
| suggestion status | `x-openregister-lifecycle` on `fieldSuggestion.status` | `suggested` to `decided`, one terminal state |
| writing accepted values | imperative, `ObjectService` as the session user | a write into another schema with the clerk's rights |
| notification | none | the clerk who asked is the one who decides |

## Seed data

New `fieldFillProfile` (1.0.0) and `fieldSuggestion` (1.0.0). The seed
adds one inactive profile for filinq's own `dossier` schema (fields
`name` and `description`), so the admin sees the shape, and nothing else.
`fieldSuggestion` declares an `x-openregister-processing` activity
(purpose: suggesting record fields from a document; the text stays on the
server unless the admin chose an external provider) and an authorization
cascade: read and decide by the requester and administrators.

## Risks / trade-offs

- An admin picks an external task-processing provider. Then document text
  leaves the server. The switch names the provider, and the docs say this
  in one sentence.
- A long document is cut to fit the prompt. The service sends the first
  4,000 characters, as `buildPrompt()` does, and the dialog says so when
  it cut.
- Moving the model call out of `FinancialExtractionService` could change
  the invoice path. Its existing unit tests run unchanged against the
  shared class.

## Open questions

- Should filinq keep an allowlist of task-processing providers it accepts
  as local, and refuse the rest? This change shows the provider and leaves
  the choice to the admin.
