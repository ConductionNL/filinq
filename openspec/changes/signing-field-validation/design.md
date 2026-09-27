# Design: signing-field-validation

Kind: code. Three settings on a placed field, one checker over
OpenRegister's formats, one extra argument on the sign act, one dialog.

## Context

Read at development `2088cc1f`.

- `signingRequest` (`lib/Settings/filinq_register.json:2353`) and
  `signerRecord` (`:2714`) carry no fields. `signerRecord` is readable and
  updatable only by `docudesk-signing-admins`; create is open to
  authenticated users.
- The open change `bulk-signing-field-builder` (0 of 15 tasks) adds
  `signingRequest.fieldPlacements` as `{signerIndex, page, x, y, width,
  height, type}` with `type` one of `signature`, `initials`, `date`,
  `text` or `checkbox` (REQ-DDBSF-001). Its design D2 has
  `NativeSigningProvider::produceSignedArtifact()` draw each block, with
  the signer's name, date or initials, before the v2 seal is computed. A
  `text` block holds no value a signer typed.
- `lib/Service/SigningService.php:383` `sign()` takes an optional
  `signatureData` and writes it with `SIGNED` in one save (:428 to :437).
  `lib/Controller/SigningController.php:296` `sign()` reads `signerId`
  (:306). `lib/Controller/PortalSigningReceiverController.php:143`
  `signDocument()` reads `consent` and `signature` (:151 to :156).
- `src/views/signing/SigningFolder.vue` signs a selection through
  `POST api/signing/folder/sign` (`appinfo/routes.php:255`).
- OpenRegister (read-only clone at `ae898b0`): `lib/Formats/` holds
  `BsnFormat`, `CronFormat`, `Iso8601DateTimeFormat`, `SemVerFormat`,
  `UserFormat`, `UuidFormat` and `ExtendedFieldTypeValidator`. There is no
  IBAN format. Formats implement `validate(mixed $data): bool`
  (`BsnFormat.php:45`), are registered in
  `lib/Service/Object/ValidateObject.php:2016`, and must also be allowed in
  `lib/Service/Schemas/PropertyValidatorHandler.php` (:234 records how a
  missing allowlist entry broke procest's BSN import).
- filinq already checks IBANs once: `lib/Service/Extraction/IbanExtractor.php:89`
  `isValidMod97()`, private, for invoice extraction.
- `signingAuditEntry` declares the processing activity `docudesk-signing`
  with data categories `PERSON`, `EMAIL` and `IP_ADDRESS`.

New: `FieldValueChecker`, the three placement settings,
`signerRecord.fieldValues`, `FillInAndSignDialog.vue`.

## Goals / Non-goals

Goals: a declared check on a signer's text field; a server that refuses a
wrong value before anything is signed; the value stored and sealed with the
signature; the check itself owned by OpenRegister.

Non-goals: checks beyond IBAN in this change, BSN fields, conditional
fields, checking who owns an account.

## Decisions

### D1. The check is declared on the placed field

A `text` placement may carry `label` (shown to the signer), `required`
(boolean) and `validation` (`iban-nl` or `iban`). Other types ignore the
three. The initiator sets them in the placement editor that
`bulk-signing-field-builder` adds.

Alternative considered: a free pattern per field. Rejected. A pattern
cannot do a checksum, and every sender would write a different one.

### D2. OpenRegister's formats do the checking

`FieldValueChecker` maps `iban` to OpenRegister's `iban` format, and
`iban-nl` to the same format with country `NL`. It resolves the format by
name, duck-typed, and adds no checksum of its own (ADR-011). When
OpenRegister has no such format, creating a request with that check is
refused with "This check is not available on this server". A signer never
meets that refusal.

Alternative considered: lift `IbanExtractor::isValidMod97()` into a shared
filinq helper. Rejected. It would make filinq the owner of a format the
whole fleet needs. The extractor's private copy stays as it is and can
move to the OpenRegister format later.

### D3. The server decides, the browser shows

The dialog sends the values with the sign act and shows the per-field
messages of a 422. It runs no checksum itself. Alternative considered: a
mod-97 check in JavaScript for instant feedback. Rejected. A second
implementation drifts from the first, and the server would still have to
check.

### D4. All or nothing, stored with the signature

`sign()` checks every value first: each required field is filled and each
value passes its check. On any failure it answers 422 with a message per
field, such as "Type a valid Dutch IBAN, for example NL91 ABNA 0417 1643
00", and writes nothing. On success the values go on
`signerRecord.fieldValues` in the same save as `SIGNED`. An IBAN is stored
in capitals without spaces and shown in groups of four.

### D5. Placements freeze when the request is sent

A stored value points at its placement by position. So once a request
leaves `DRAFT`, the service refuses a change to `fieldPlacements`.

### D6. The value is sealed

The native provider draws each value in its field block before the v2
seal, extending `bulk-signing-field-builder` D2. Changing a value in the
signed file afterwards fails verification.

### D7. Where a signer fills in

In the signing folder, a request with fields for this signer opens a
"Fill in and sign" dialog. Such a request cannot be signed as part of a
folder selection; the folder says "This document has fields to fill in.
Open it to sign." On the portal, the receiver's sign act takes a
`fields` object beside `consent` and `signature`.

### D8. Personal data

Only the fields the initiator placed are collected (AVG art. 5(1)(c)). The
check serves accuracy (art. 5(1)(d)). Values keep `signerRecord`'s read
restriction and its retention. The `docudesk-signing` processing activity
gains the data category `IBAN` (art. 30). Values never go into a log line,
an exception message or audit metadata.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| which check a field has | declared on the placement | data on the request |
| the check itself | OpenRegister format, called by name | ADR-011; one owner for the fleet |
| refusing a wrong value | imperative, in `sign()` | must run before the signature is written, in the same act |
| notification | none | the signer is the one acting |

## Seed data

`fieldPlacements` items gain `label` (string), `required` (boolean) and
`validation` (string, enum `iban-nl`, `iban`). `signerRecord` gains
`fieldValues` (array of `placement`, `label`, `value`, `validation`).
The seed adds one request with a required `iban-nl` field labelled
"Account for the payment" and one signed signer whose value is the
published example IBAN `NL91ABNA0417164300`. Register version bump.

## Risks / trade-offs

- The check proves the number is well formed, not that it belongs to the
  signer. The docs say so.
- Until openregister ships the IBAN format, no request can use the check.
  The refusal at creation says why.
- Until portaliq's row action collects inputs, an external signer cannot
  sign a request with required fields. The in-app path works.

## Open questions

- Should the initiator see the values on the request detail page, or only
  admins in `docudesk-signing-admins`? The design keeps the schema's read
  restriction until someone decides otherwise.
