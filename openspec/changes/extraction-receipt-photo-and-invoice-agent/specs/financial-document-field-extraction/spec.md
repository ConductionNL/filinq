# financial-document-field-extraction Specification (delta)

## Purpose

Each extraction says whether it can be trusted without a person, and why
not. The consumer decides what to do with that. Matrix row `pur-ai-agent`
(shillinq matrix, owned by filinq).

## ADDED Requirements

### Requirement: Each extraction carries an unattended verdict with its reasons (REQ-ERP-003)

When an extraction completes, Filinq MUST compute a verdict, `pass` or
`review`, and MUST store it on the `financialExtraction` as
`unattendedVerdict` with `verdictReasons` and the `verdictThreshold` used.
The verdict MUST be `pass` only when `supplierName`, one supplier
identifier (`supplierIban`, `supplierKvk` or `supplierVatId`), `issueDate`
and `totalIncl` are present, each at or above the threshold; the totals
reconciled; no field was filled by the model alone; and no earlier
extraction has the same supplier identifier and invoice number. Each
failed check MUST add one reason code: `missing-field`, `low-confidence`,
`not-reconciled`, `model-only` or `possible-duplicate`. The stored verdict
MUST NOT change when the threshold changes later.

Rows: `pur-ai-agent` (shillinq matrix)

#### Scenario: A clean invoice from a known supplier passes

- GIVEN a supplier invoice with a valid IBAN, an invoice number, an issue date and totals that reconcile, all read at confidence 0.95, with a threshold of 0.9
- WHEN it is extracted
- THEN `unattendedVerdict` is `pass` and `verdictReasons` is empty
- @e2e exclude backend pipeline with no filinq screen; covered by PHPUnit on `UnattendedVerdict`

#### Scenario: The same invoice sent twice goes to review

- GIVEN an earlier extraction with IBAN NL91ABNA0417164300 and invoice number 2026-0412
- WHEN a second file with the same IBAN and invoice number is extracted
- THEN `unattendedVerdict` is `review` with the reason `possible-duplicate`
- @e2e exclude backend pipeline; covered by PHPUnit with two fixture extractions

### Requirement: The verdict is sent as a sibling event (REQ-ERP-004)

After dispatching `nl.conduction.filinq.extraction.completed`, Filinq MUST
dispatch `nl.conduction.filinq.extraction.verdict` when `callbackEvent` is
true, carrying `extractionId`, `documentUri`, `sourceApp`, `docType`,
`verdict`, `reasons` and `threshold`. The payload of
`extraction.completed` MUST stay exactly as REQ-FIN-05 lists it. A failure
to dispatch the verdict event MUST be logged and MUST NOT fail the
extraction.

Rows: `pur-ai-agent` (shillinq matrix)

#### Scenario: shillinq receives the verdict for a captured invoice

- GIVEN shillinq listening on `nl.conduction.filinq.extraction.verdict`
- WHEN an invoice extraction with `callbackEvent` true completes with verdict `review`
- THEN shillinq receives the verdict event with the extraction id and the reasons, and the completed event it already handles is unchanged
- @e2e exclude event contract between two apps; covered by PHPUnit on the dispatcher and a payload snapshot test

### Requirement: An admin sets the confidence a pass needs (REQ-ERP-005)

The admin settings MUST offer the unattended confidence threshold, default
0.9, accepting values from 0.6 to 1.0 and refusing others with "Choose a
value between 0.6 and 1.0."

Rows: `pur-ai-agent` (shillinq matrix)

#### Scenario: An admin raises the threshold

- GIVEN an admin on the filinq admin settings
- WHEN the admin sets the unattended threshold to 0.95 and saves
- THEN the page shows 0.95 after a reload, and a value of 0.5 is refused with "Choose a value between 0.6 and 1.0."
- @e2e tests/e2e/extraction-verdict-settings.spec.ts
