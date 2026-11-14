---
kind: code
depends_on: [bulk-signing-field-builder]
---

# Proposal: signing-field-validation

Matrix row `sig-field-validation` in filinq's
`openspec/parity/capabilities.json`, rated no, built.state none. Written in
the OpenSpec pass of 27 September 2026.

## Why

A subsidy agreement asks the applicant for the bank account the money goes
to. The applicant types it while signing. One digit wrong and the payment
bounces weeks later, after the agreement is signed and filed. A check at
the moment of typing would have caught it.

filinq cannot check it, because a signer types nothing. The `signingRequest`
and `signerRecord` schemas carry level, mode, status and provider, and no
fields (`lib/Settings/filinq_register.json:2353`, `:2714`).
`src/views/signing/SigningRequestForm.vue` collects no fields. Placing
fields on a document is designed in the open change
`bulk-signing-field-builder`, with 0 of 15 tasks done, and even there a
`text` field is a box on the page, not a value the signer fills in.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `sig-field-validation` | Check what a signer types in a form field, such as a valid IBAN. | no: no signer form fields at all; field placement is designed only (`bulk-signing-field-builder`, 0 tasks done) |

### Demand

- changelog: https://kb.validsign.eu/nl/release-notes/release-notes-2026-09-23

### Competitors rated yes

- ValidSign (docs read 2026-09-26): "validation types on text fields, Dutch
  IBAN check first, queryable through the API". Evidence:
  https://kb.validsign.eu/nl/release-notes/release-notes-2026-09-23

## What changes

- A `text` field placed for a signer gets a label, whether it is required,
  and a check: a Dutch IBAN or any IBAN, to start with.
- The signer fills the fields in while signing. filinq checks every value
  on the server before the signature is written. A wrong value is refused
  with a message on that field, and nothing is signed.
- The checks are OpenRegister's formats, not a new validator in filinq
  (ADR-011).
- The values are stored on the signer's record, drawn into the signed
  document inside the tamper-evident seal, and readable through the API by
  those allowed to read signer records.
- An internal signer fills them in from the signing folder. An external
  signer sends them with the portal sign act.

## Capabilities

### New capabilities

- `signing-field-validation`: signer-filled text fields with a declared
  check, validated on the server before signing, stored and sealed with the
  signature.

### Modified capabilities

None in `openspec/specs/`. This change extends the placement shape of the
open change `bulk-signing-field-builder` (REQ-DDBSF-003) and depends on it.

## Impact

- `lib/Settings/filinq_register.json`: `signingRequest.fieldPlacements`
  items gain `label`, `required` and `validation`; `signerRecord` gains
  `fieldValues`; the signing processing activity gains the data category
  `IBAN`. Register version bump.
- New `lib/Service/Signing/FieldValueChecker.php`.
- `lib/Service/SigningService.php`: `sign()` takes field values and checks
  them first. `lib/Controller/SigningController.php` and
  `lib/Controller/PortalSigningReceiverController.php` pass them through.
- `lib/Service/Signing/NativeSigningProvider.php`: draws each value in its
  field block before the seal.
- `src/views/signing/SigningFolder.vue` and a new dialog in `src/modals/`.
  The placement editor from `bulk-signing-field-builder` gets the three
  settings.
- `docs/features/`: a section with a screenshot.

## Out of scope

- Checks beyond IBAN in this change. The list is open; each new check is
  one OpenRegister format.
- A burgerservicenummer (BSN) field. Asking a signer for a BSN needs a
  legal basis most senders lack, so it is not offered.
- Conditional fields and formulas, which `bulk-signing-field-builder` also
  leaves out.

## Cross-app dependencies

- **openregister**: an IBAN format. `lib/Formats/` holds `BsnFormat`,
  `CronFormat`, `Iso8601DateTimeFormat`, `SemVerFormat`, `UserFormat` and
  `UuidFormat`, and no IBAN check. openregister must add `IbanFormat`
  (ISO 13616 mod-97, with an optional country), register it in
  `ValidateObject::getValidator()` (`lib/Service/Object/ValidateObject.php:2016`)
  and allow it in `PropertyValidatorHandler` (`:234`), and let an app check
  one value against a named format without saving an object.
- **portaliq**: the portal sign row action must collect the field values
  filinq declares for the signer and send them with the sign act. Today a
  row action carries no inputs.
