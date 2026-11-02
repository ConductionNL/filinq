---
kind: code
depends_on: [document-intake-inbox]
---

# Proposal: intake-acknowledge-and-stamp

Matrix rows `in-ack` and `in-stamp` in filinq's
`openspec/parity/capabilities.json`, both rated no, built.state none.
Written in the OpenSpec pass of 27 September 2026.

## Why

A letter arrives and a registrar puts it in the intake inbox. Two things
still happen by hand. The sender gets no word that the letter arrived, so
they phone to ask. And the paper, or its scan, carries no visible mark of
when it came in: the received date lives only in `receivedAt` on the
`intakeDocument`, which nobody sees on a printout.

Both happen at the same moment, when a document arrives through
`IntakeService::receive()` (`lib/Service/IntakeService.php:95`), and both
are chosen per channel and sender, which is exactly what an intake default
rule already matches on (`IntakeDefaultRuleService::match()`,
`lib/Service/IntakeDefaultRuleService.php:87`). So one change covers both.

Both rows sit in intake, filinq's core area.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-ack` | Send the sender an acknowledgement of receipt automatically when a document is registered. | no: every `acknowledg*` hit in `lib/` is a print-job or override acknowledgement; no template or send on registration |
| `in-stamp` | Put a visible stamp, such as received with the date, on a document. | no: only metadata stamps (`IntakeDefaultRuleService.php:135`); the waarmerk stamp page of `document-waarmerk-certification` is designed only and marks outgoing documents |

### Demand

- featureRequest (`in-stamp`): https://github.com/paperless-ngx/paperless-ngx/discussions/3744

### Competitors rated yes

None rates either row yes. Paperless-ngx is partial on `in-ack`: "a
Document Added workflow can send an email automatically
(src/documents/models.py:1589 WorkflowActionEmail), but recipients are a
fixed comma list, not the sender's address"
(https://github.com/paperless-ngx/paperless-ngx, v3.2.1). Both rows are
built for the core-area rule, not for a competitor.

## What changes

- An intake default rule can say "acknowledge": which template to use for
  the acknowledgement. When a matching document arrives, filinq renders the
  acknowledgement from that template with the arrival's data (subject,
  received date, registration number when there is one).
- The acknowledgement reaches the sender through OpenRegister's party
  notification: over the party's correspondence address when the sender
  is a known party, and over the sender's e-mail address when the document
  came in by mail. A party with a refuse-send indicator is not written to,
  and the intake document says so.
- A sender filinq cannot reach by e-mail (paper, digital post without an
  address) gets the acknowledgement as a letter in the outgoing post, not
  in silence.
- An intake default rule can say "stamp received". For a PDF (and a scan,
  which is a PDF), filinq writes a stamped rendition with "Ontvangen" and
  the received date, and the registration number when there is one, on the
  first page. The arrived file is never changed.
- The intake worklist shows whether a document was acknowledged and opens
  the stamped rendition when there is one.

## Capabilities

### New capabilities

- `intake-acknowledgement`: acknowledge receipt to the sender and stamp the
  received date on a rendition, both chosen by the intake default rule.

### Modified capabilities

None. `document-intake-inbox` keeps its rules; the default rule gains two
optional fields.

## Impact

- `lib/Settings/filinq_register.json`: `intakeDefaultRule` gains
  `acknowledgeWith` (template reference) and `stampReceived` (boolean);
  `intakeDocument` gains `acknowledgement` (object) and `stampedRendition`
  (file id). Register version bump and seed.
- New `lib/Service/IntakeAcknowledgementService.php` and
  `lib/Service/ReceivedStampService.php`, called from a listener on the
  saved intake document, never from inside `receive()`.
- `src/views/intake/IntakeIndex.vue`: an acknowledged column and an "Open
  stamped copy" row action.
- `docs/features/`: a section with screenshots.

## Cross-app dependencies

- openregister: `PartyNotificationService::notifyParties()` sends a plain
  body today. filinq needs the rendered letter as the body or an
  attachment; if the send unit cannot carry an attachment, the letter goes
  as text and the PDF stays on the record. No new endpoint is asked for.

## Out of scope

- Sending other outgoing letters by e-mail (`sh-email`, deferred).
- Stamps on Office files. They are not changed; the worklist says no stamp
  was made.
- A stamp on the arrived file itself.
