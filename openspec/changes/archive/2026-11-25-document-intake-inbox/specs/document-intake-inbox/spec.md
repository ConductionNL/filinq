# document-intake-inbox Specification (delta)

---
status: proposed
---

## Purpose

Documents from a scanner, a mailbox or digital post wait in one inbox until a
clerk assigns them to a case or rejects them. Filinq owns the inbox and the
assign action. dossiq renders the inbox as a leaf (ADR-066, render-surface)
and reads the result. Requested by the dossiq competitor analysis, finding B03.

## ADDED Requirements

### Requirement: Intake document schema (REQ-DII-01)

Filinq MUST declare an `intakeDocument` schema in the `document` register with
`channel` (enum `scan`, `mail`, `digitalPost`), `file`, `receivedAt`, `sender`,
`subject`, `sourceRef`, `assignedTo`, `assignedBy`, `assignedAt`,
`rejectReason` and a declarative `x-openregister-lifecycle` on `status` with
initial `received` and terminal states `assigned` and `rejected`. Property
names MUST be English (dossiq decision D13).

#### Scenario: Register import creates the schema

- GIVEN a Nextcloud instance with filinq and OpenRegister
- WHEN `ConfigurationService::importFromApp()` runs on boot
- THEN the `intakeDocument` schema exists in the `document` register with the lifecycle above
- @e2e exclude register import runs at boot with no UI surface; covered by the register-import PHPUnit test

### Requirement: Feeders create intake documents through an event (REQ-DII-02)

Any channel that receives a document MUST dispatch an
`IntakeDocumentReceivedEvent` carrying the channel, the file and the sender
metadata. Filinq MUST listen and create one `intakeDocument` in `received`.
A feeder MUST NOT assign the document to a case.

#### Scenario: A scanned file becomes an intake document

- GIVEN the OCR folder watch sees a new PDF
- WHEN the watch dispatches `IntakeDocumentReceivedEvent` with channel `scan`
- THEN one `intakeDocument` exists with `status = received`, `channel = scan` and the file reference
- @e2e exclude folder-watch feeder runs as a background job; covered by PHPUnit on `IntakeService::receive()`

### Requirement: Assign or reject with server-side checks (REQ-DII-03)

`IntakeService::assign()` MUST set `assignedTo` to `{register, schema, id}`,
record `assignedBy` and `assignedAt`, move the file to the target's folder when
the target owns one, and transition to `assigned`. `reject()` MUST require a
reason and transition to `rejected`. Both MUST refuse a user without write
rights on the intake register; assign MUST also refuse a user without write
rights on the target object.

#### Scenario: A clerk assigns a document to a case

- GIVEN an `intakeDocument` in `received` and a case object the clerk may write
- WHEN the clerk assigns the document to that case
- THEN the document is `assigned` with `assignedTo` pointing at the case and the file sits in the case folder
- e2e: `tests/e2e/intake-inbox.spec.ts`

#### Scenario: Assign to an object the clerk may not write is refused

- GIVEN an `intakeDocument` in `received` and a case object the clerk may only read
- WHEN the clerk tries to assign the document to that case
- THEN the service answers 403 and the document stays `received`
- @e2e exclude authorization guard; covered by PHPUnit on `IntakeService::assign()`

### Requirement: The intake inbox page (REQ-DII-04)

Filinq MUST ship a manifest page `intake` listing `intakeDocument` objects in
`received`, with a preview of the selected file, an assign dialog with an
object picker and a reject dialog that asks for a reason. The page MUST use
`CnIndexPage` and `CnFormDialog`.

#### Scenario: The inbox shows waiting documents

- GIVEN two seeded intake documents in `received`
- WHEN a clerk opens the Intake page
- THEN both documents are listed with channel, sender, subject and received date, and selecting one shows its preview
- e2e: `tests/e2e/intake-inbox.spec.ts`

### Requirement: The inbox is a render-surface leaf (REQ-DII-05)

Filinq MUST register a leaf with id `filinq-document-intake` on both halves:
a `LeafDescriptor` of kind `render-surface` through
`RegisterLeafProvidersEvent`, and a JS `registerIntegration()` under the same
id with a `tab` and a `widget`. The leaf takes the host object and an optional
filter, renders the inbox scoped to it, and offers assign to this object. The
leaf MUST NOT invoke any action in the consuming app (ADR-066 decision 2).

#### Scenario: dossiq places the leaf on a case

- GIVEN filinq and dossiq are installed and dossiq's case detail page places `filinq-document-intake`
- WHEN a clerk opens a case
- THEN the widget lists received documents and assigning one sets `assignedTo` to that case
- e2e: `tests/e2e/intake-leaf.spec.ts`

#### Scenario: Descriptor and JS registration agree

- GIVEN the leaf is registered
- WHEN gate-24 (integration parity) inspects the app
- THEN the descriptor and the JS registration share the id and both `tab` and `widget` exist
- @e2e exclude parity is checked mechanically by gate-24, not in a browser
