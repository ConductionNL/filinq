---
kind: code
depends_on: []
---

# Proposal: extraction-model-filled-fields

Matrix row `wk-ml-fields` in filinq's `openspec/parity/capabilities.json`
(rated partial, built.state built) and row `plt-ai-fill` from shillinq's
matrix (rated partial there, owned by filinq). Written in the OpenSpec pass
of 27 September 2026.

## Why

A registrar files a subsidy application on its record and then types the
applicant, the amount and the requested start date into the record by
hand, reading them off the document. filinq can already have a model read
a document, but only for one fixed set of fields.

`FinancialExtractionService::extractFinancial()`
(`lib/Service/FinancialExtractionService.php:236`) runs heuristics and then
`applyAiEnhancement()` (:509), which sends a Nextcloud TaskProcessing
`TextToText` task (`runAiTask()`, :869, task built at :877) for the
invoice fields it could not fill. The field list is a constant,
`FIELD_DEFAULTS` (:84): supplier, IBAN, KvK, VAT id, invoice number,
dates, totals, lines. The document type is one of `receipt` or
`supplier-invoice` (`VALID_DOC_TYPES`, :76). The only caller is shillinq,
through `POST api/extraction/financial` (`appinfo/routes.php:276`).

General enrichment does not help. `MetadataService::enhanceTextMetadata()`
(`lib/Service/MetadataService.php:100`) fills `language`, `keywords` and
`topic` with word counts, and never the fields a record's own schema
declares.

The open change `inbound-auto-classification` suggests a document type and
a correspondent from vocabularies and detected entities. Its non-goals say
"No model training / learned classification this wave" and it adds no
field of a record schema. It does not cover this row.

### Matrix rows

filinq `openspec/parity/capabilities.json`:

| row | capability | filinq today |
|---|---|---|
| `wk-ml-fields` | Have a document's own metadata fields filled in from its content by a model. | partial: a model fills a fixed set of invoice fields for one consumer (`FinancialExtractionService.php:509`, :877); enrichment fills language, keywords and topic by heuristics |

shillinq `openspec/parity/capabilities.json`:

| row | capability | filinq today |
|---|---|---|
| `plt-ai-fill` | Have an assistant fill in a record's fields from a prompt or an attached file. | partial: only purchase invoices and receipts, from an attached file, not a general prompt |

`plt-ai-fill` comes from shillinq's matrix and is owned by filinq. The
missing half of both rows, which this change specifies: a model fills the
fields a document type or record schema declares, from the attached file,
not only the fixed invoice fields for one consumer. The free-prompt half
of `plt-ai-fill` belongs to the assistant, hermiq.

### Demand

- featureRequest: https://github.com/paperless-ngx/paperless-ngx/discussions/6932
- changelog: https://www.odoo.com/odoo-19-release-notes

### Competitors rated yes

- Moneybird (`plt-ai-fill`): "Moneybird vult het type, contact, datum,
  bedragen en factuurregels zo volledig mogelijk in" from the attachment,
  and "an AI tool can create contacts, invoices and more from a prompt via
  MCP". Evidence:
  https://helpcenter.moneybird.nl/nl/articles/207134-verwerk-inkoopfacturen-en-bonnen
  and https://helpcenter.moneybird.nl/nl/articles/396511-ai-koppeling
- Odoo (`plt-ai-fill`): "Use AI to fill in fields", "Ask AI to use file
  content when updating fields or performing actions" (Enterprise AI).
  Evidence: https://www.odoo.com/odoo-19-release-notes

For `wk-ml-fields` no competitor is rated yes. Paperless-ngx is partial:
its LLM suggests title, tags, correspondents, document types, storage paths
and dates, and "Custom fields are not suggested"
(`src/paperless_ai/ai_classifier.py:266-271`, read at v3.2.1).

## What changes

- A consuming app or an admin declares, per record schema, which fields a
  model may fill. Nothing else is ever filled.
- For a file on a record, filinq reads the text, asks the model for the
  declared fields, and checks every answer against the field's type, format
  and allowed values. An answer that does not fit is dropped.
- Each suggestion shows the passage it came from, or says it was not found
  in the text.
- A clerk accepts, changes or rejects each suggestion. Only accepted values
  are written, as the clerk, with the clerk's rights. Every decision is
  kept, so the next suggestion can be judged against it.
- The first screen is the intake worklist: "Fill fields from document" on a
  document that has been assigned to a record. Other apps call the same API
  and show the suggestions in their own forms.
- The model runs through Nextcloud's own task processing. The feature is
  off until an admin switches it on, and the settings page names the
  provider that will read the documents.

## Capabilities

### New capabilities

- `schema-field-suggestions`: model suggestions for the declared fields of
  any record schema, read from a file and accepted field by field.

### Modified capabilities

None. `financial-document-field-extraction` keeps its fixed invoice fields
and its heuristics; it shares the model call this change moves into one
place.

## Impact

- `lib/Settings/filinq_register.json`: new schemas `fieldFillProfile` and
  `fieldSuggestion`. Register version bump.
- New `lib/Service/SchemaFieldFillService.php`; the model call
  (`resolveAiManager()`, `runAiTask()`) and the text step (`resolveText()`)
  of `FinancialExtractionService` move into shared classes both use.
- Routes: `POST api/extraction/fields` and
  `POST api/extraction/fields/{id}/decide`.
- `src/views/intake/IntakeIndex.vue`: a row action; new
  `src/dialogs/FieldSuggestionDialog.vue`.
- `src/views/settings/Settings.vue`: the switch, the provider name and the
  declared profiles.
- `docs/features/`: a section with a screenshot.

## Out of scope

- Filling a record from a free prompt with no file. That is hermiq's.
- Training or tuning a model on the decisions. They are kept, not used.
- Writing a value without a person. There is no unattended mode here.
- A field with the `bsn` format (UAVG art. 46). A profile that names one
  is refused.

## Cross-app dependencies

- hermiq: the free-prompt half of `plt-ai-fill`, filling a record from what
  the user types, as an assistant action on the record.
- Consuming apps (shillinq, dossiq and others) MAY declare a
  `fieldFillProfile` for their schemas and show the suggestions in their
  own record forms through `POST api/extraction/fields`.
