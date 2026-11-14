# Design: intake-acknowledge-and-stamp

Kind: code. Two optional fields on a rule, two services, one listener.

## Context

Read at development `2088cc1f`.

- `lib/Service/IntakeService.php:95` `receive()` builds the
  `intakeDocument`, stamps defaults through
  `IntakeDefaultRuleService::stamp()` (:141), adds a `partySuggestion`
  (:148) and saves through `IntakeRepository::save()` (:151). Channels are
  scan, mail and digital post (`lib/Event/IntakeDocumentReceivedEvent.php:71`).
- `lib/Service/IntakeDefaultRuleService.php:87` `match(channel, sender)`
  picks the lowest-order active rule; `:135` `stamp()` writes the rule's
  `stamps` to `stampedDefaults`. The `intakeDefaultRule` schema
  (`lib/Settings/filinq_register.json`, `components.schemas.intakeDefaultRule`)
  has `name`, `channel`, `senderPattern`, `stamps`, `order`, `active`.
- `intakeDocument` has `sender`, `receivedAt`, `file`, `stampedDefaults`,
  `defaultRule` and a lifecycle on `status` (received, assigned, rejected,
  detached).
- `lib/Service/DocumentService.php:168` `generateDocument(templateId,
  dataRefs, options)` renders a template to a format.
- `documentRegistration` (`direction`, `registrationNumber`, `answers`) is
  the post register of `documents-in-and-out-of-the-building`; its number is
  drawn from a platform sequence.
- PDF pages are imported and redrawn with FPDI in
  `lib/Service/PdfDocumentFactory.php:90` and
  `lib/Service/GrondslagenPdfWriter.php:336`.
- openregister `lib/Service/Party/PartyNotificationService.php:107`
  `notifyParties(objectUuid, subject, body, role)` reaches parties over
  their correspondence address and leaves out a party with a refuse-send
  indicator; `lib/Service/Notification/EmailSender.php:135`
  `sendToAddress()` sends to a bare address.
- `src/views/intake/IntakeIndex.vue:172` defines the columns; row actions
  at :62.

New: the two services, the listener, the four properties.

## Goals / Non-goals

Goals: acknowledge and stamp per rule, at arrival, without slowing
`receive()`; never change the arrived file; never write to a party that
refused.

Non-goals: general outgoing mail, stamps on Office files, a stamp editor.

## Decisions

### D1. The rule decides, not a new rule type

`intakeDefaultRule` gains `acknowledgeWith` and `stampReceived`. The rule
already matches on channel and sender and already names itself on the
document. Alternative considered: a separate `intakeAcknowledgementRule`
schema. Rejected: two rule sets matching the same arrival would need their
own precedence.

### D2. After the save, never inside receive()

A listener on the saved `intakeDocument` (OpenRegister object created
event, filtered to the `intakeDocument` schema) runs the two services. A
failed acknowledgement or stamp never refuses an arrival. The outcome is
written to `acknowledgement` (`{status: sent|queued-as-letter|refused|failed,
address, at, reason}`) on the document.

### D3. Reaching the sender

1. The document names a party (a confirmed party suggestion): use
   `notifyParties(role: 'sender')`.
2. The channel is mail and `sender` is an e-mail address: use
   `sendToAddress()`.
3. Otherwise: store the rendered letter as an outbound document for the
   outgoing post and set status `queued-as-letter`.
A party refused by an indicator is status `refused` with the indicator's
reason. Alternative considered: Nextcloud's mailer directly from filinq.
Rejected: openregister already owns the send unit and the refuse-send
indicators.

### D4. Registration number when there is one

When an inbound `documentRegistration` names this document, the
acknowledgement and the stamp carry its `registrationNumber`, and the
acknowledgement is registered as an outbound `documentRegistration` whose
`answers` names the inbound one. Without a registration, both carry the
received date only.

### D5. The stamp is a rendition

`ReceivedStampService` imports every page of the PDF with FPDI and draws
the stamp on page 1, top right: "Ontvangen", the date in the user's locale
and the number. The result is written beside the file as
`<name> (ontvangen).pdf` and its id stored in `stampedRendition`. The
arrived file keeps its bytes and checksum. Alternative considered: a new
Nextcloud version of the file. Rejected: the arrived file is evidence.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| which rule applies | declarative, `intakeDefaultRule` fields | the rule set is data an admin edits |
| sending | imperative, openregister party notification | the declarative dialect addresses uids, the sender has no account |
| stamping | imperative service | a file operation |
| outcome on the document | declarative property, written once | read by the worklist |

## Seed data

One `intakeDefaultRule` for channel `mail` with `acknowledgeWith` set to a
seeded template "Ontvangstbevestiging" and `stampReceived: true`, and one
seeded `intakeDocument` carrying a sent `acknowledgement` and a
`stampedRendition`.

## Risks / trade-offs

- A spam wave triggers acknowledgements. The rule can be narrowed by
  sender pattern, and a document rejected within the same minute is not
  acknowledged (the listener checks status before sending).
- SMTP not configured. The outcome is `failed` with the send unit's
  reason, visible in the worklist.

## Open questions

- Should the acknowledgement wait for assignment on channels where spam is
  common? Default: send on arrival, since the rule is opt-in per channel.
