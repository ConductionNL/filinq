# Design: document-intake-inbox

Kind: code. A schema, a service, a page and a leaf registration.

## Context

Filinq keeps documents as OpenRegister objects in the `document` register
(`lib/Settings/filinq_register.json`) and reads them through
`SettingsService::getObjectService()`. It already watches a folder for OCR
(`ocr-document-scanning`). What it lacks is the waiting room: a document that
exists but belongs to nobody yet.

The consumer is dossiq. Per ADR-066 dossiq may only render and read a filinq
leaf. So the assign action runs in filinq's own bundle and DI context, and the
result reaches dossiq as data on the object, never as a call into dossiq.

## D1. One schema, `intakeDocument`

Added to the `document` register, English names per dossiq decision D13.

| property | type | notes |
|---|---|---|
| `channel` | enum `scan`, `mail`, `digitalPost` | who fed it |
| `file` | file reference | the received file, in a filinq intake folder |
| `receivedAt` | date-time | set by the feeder |
| `sender` | string | address, sender name or scanner id |
| `subject` | string | mail subject or first OCR line |
| `sourceRef` | string | feeder's own id (mail message id, berichtenbox id) |
| `status` | lifecycle `received`, `assigned`, `rejected` | `x-openregister-lifecycle`, initial `received` |
| `assignedTo` | object reference `{register, schema, id}` | set on assign |
| `assignedBy`, `assignedAt` | string, date-time | audit |
| `rejectReason` | string | required on reject |

`hardValidation: true`. The lifecycle is declarative; there is no state
machine in PHP. `assigned` and `rejected` are terminal.

## D2. `IntakeService`

`lib/Service/IntakeService.php`, Controller to Service to OpenRegister
(ADR-008). Three methods:

- `receive(channel, file, meta)`: creates an `intakeDocument`. Called by the
  feeders (OCR watch, integriq listeners) through a typed
  `IntakeDocumentReceivedEvent` so no feeder imports filinq classes.
- `assign(id, target, userId)`: moves the file into the target's folder when
  the target owns one, sets `assignedTo`, transitions to `assigned`.
- `reject(id, reason, userId)`: transitions to `rejected`, keeps the file for
  the retention period.

Authorization: assign and reject require write on the intake register and,
for assign, write on the target object. Both are checked server-side in the
service, not only in the UI.

## D3. The page

A manifest page `intake` of type `index` over `document/intakeDocument`
filtered to `status = received`, with a preview panel and two actions. Uses
`CnIndexPage` and `CnFormDialog` (ADR-012). The assign dialog uses an object
picker over any register the user may write to.

## D4. The leaf, ADR-066 `render-surface`

- Server: an `IEventListener` on `RegisterLeafProvidersEvent` adds a
  `LeafDescriptor` with id `filinq-document-intake`, kind `render-surface`,
  `renderMode: component`.
- Client: `registerIntegration()` under the same id with both a `tab` and a
  `widget` (gate-24 parity). Props: the host object `{register, schema, id}`
  and an optional `filter` (for dossiq: the case type).
- The widget lists received documents and offers assign to this object. The
  tab is the full inbox filtered to the object.
- No verb crosses the seam. dossiq reacts to the `assignedTo` write through
  its own `zaakinformatieobject` relation, which is dossiq's call.

## D5. Feeders

The OCR folder watch creates an `intakeDocument` with channel `scan` instead
of a plain document. Mail and digital post arrive through integriq changes
(`mail-intake-creates-cases`, `berichtenbox-digital-post-adapter`), which
dispatch `IntakeDocumentReceivedEvent`. Filinq listens; integriq never imports
filinq.

## Risks

- A feeder that runs while filinq is absent has nowhere to put the document.
  The integriq changes keep the file on the source until the event has a
  listener.
- Rejected documents still hold personal data. They follow the document
  register's retention, not a separate rule.
