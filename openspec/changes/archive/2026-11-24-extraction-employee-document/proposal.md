---
kind: code
depends_on: []
---

# Proposal: extraction-employee-document

## Decision

humaniq's `hiring-document-to-employee` waits on a filinq contract for reading an employment
contract and an identity document. Its draft (`for-ruben/filinq-employee-document-extraction.md`)
was never written. Decided under the delegation of 22 Nov 2026, from humaniq's
`hire-to-employee` spec (REQ-HTE-003), the same way as `extraction-cv-profile`:

- **One read-only call, no stored object.** `EmployeeDocumentExtractionService::extractEmployeeDocument(array $data, string $requestedBy)`
  answers each value with its confidence. humaniq records the attempt on its own
  `EmployeeDocumentExtraction` and writes only empty fields; filinq keeps no copy of an
  identity document's data.
- **The identity document is read from its machine-readable zone** (ICAO 9303: passport TD3,
  identity card TD1), with the zone's own check digits deciding the confidence (0.95 when they
  hold, 0.5 when one fails). The visual zone is not read. No authenticity check (that is
  humaniq's `people-dossier-completeness`).
- **The contract is read with fixed rules** (labels and phrases, Dutch and English), each value
  at 0.8 after a label and 0.6 from a phrase. No model: personal data stays on the instance.

## What Changes

- `Service\Extraction\MrzParser`, `Service\Extraction\EmploymentContractParser`.
- `Service\EmployeeDocumentExtractionService::extractEmployeeDocument()` over `DocumentTextSource`.

## Cross-app

- **humaniq** `hiring-document-to-employee` 1.3 calls it duck-typed, by class name.
