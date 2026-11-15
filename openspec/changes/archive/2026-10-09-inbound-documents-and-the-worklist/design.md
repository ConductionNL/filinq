# Design: inbound documents, classification and the worklist

Kind: code. Two schemas, three services, two leaves, one background job.

## Context

Three changes already hold parts of this ground.
`inbound-auto-classification` suggests a document type and a
correspondent through a stateless classifier beside `LanguageClassifier`,
and routes to a department. `document-intake-inbox` gives a waiting
document a record with a channel and a `received`, `assigned`,
`rejected` lifecycle, plus a leaf per ADR-066.
`ocr-document-scanning` and `ocr-trigger-surface` own text recognition.

What none of them has: the attachment as a record of its own, defaults
stamped before classification, the NAW suggestion, the detached-document
worklist, and routing declared per record type.

The register is `filinq`, one register carrying all schemas since
descriptor v8.0.0. New schemas go there.

## D1. One intake record per attachment, linked to the message

An arriving message with four attachments produces five records: the
message and four attachments, each carrying `arrivedWith` pointing at the
message record. Assigning the message offers to assign the attachments
with it, and assigning one attachment alone is allowed and recorded.

The alternative, one record with four files on it, loses the case where
three bijlagen belong to this case and the fourth was sent by mistake,
which is the ordinary case in a postkamer.

## D2. Defaults are stamped at creation, enrichment runs after

`MetadataService::enhanceMetadata()` enriches on create and update and
skips a populated field. That is the right shape for a derived fact and
the wrong one for a default, because a default must exist before anything
reads the record. So the intake record is created with the channel's and
the sender's declared defaults already on it, and the classifier then
suggests changes to them rather than filling a blank.

Defaults are declared per channel and per sender pattern in filinq's
admin settings, and the record says which default it was stamped with and
by which rule.

## D3. The NAW suggestion is a suggestion

`EntityDetectionService` and OpenRegister's text extraction already find
PERSON, ORGANIZATION, LOCATION, EMAIL and PHONE_NUMBER occurrences. The
intake record reads them and offers a party suggestion with its
confidence and its source span. A clerk accepts, edits or rejects it, and
the decision is recorded as a correction, the same corpus shape
`GlAccountSuggestionService::recordBooking()` established.

Nothing is written to a party record without a person accepting it.
Extraction into an authoritative register is exactly the place where a
silent write becomes an incorrect address on a besluit.

## D4. The worklist is a state, not a second store

A document detached from a record returns to the intake record it came
from, or gets one if it never had one, with `status: detached`, the
reason and who detached it. One store, one worklist, one lifecycle. A
separate table of orphans is a second place to look and the one nobody
looks at.

## D5. Routing and acceptance are declared by the consumer

filinq does not know what a zaaktype is. The consuming app declares, per
record type, who an inbound document routes to and whether acceptance is
required. filinq stores the declaration against the type reference and
applies it. That keeps the case-type vocabulary in dossiq, per ADR-022.

## D6. The inbox is an object from the first byte

The intake record is created before the file is classified and before
anybody looks at it, as an OpenRegister object under the schema's
authorization cascade. The register descriptor's own history is the
argument: an unconfigured cascade is open, and 20 of 21 schemas shipped
without one until v7.9.0, with a measured cross-user write. A staging
table would have no cascade at all.

## D7. What the OCR read is what the search finds

The extracted text is written to the document's searchable content
through the existing OCR path, so a scan is found by its words. The
extraction is local, per filinq's rule that document processing does not
leave the instance.

## Risks

- **A classifier that is confidently wrong.** Every suggestion carries
  its confidence and nothing files itself. The acceptance step is where a
  human is, and it is declared per record type rather than assumed.
- **Attachments assigned apart from their message.** Allowed, and
  recorded on both records, so the split is visible rather than silent.
- **A default nobody can find.** The record names the rule that stamped
  it. A default applied by an invisible rule is the hardest kind of wrong
  metadata to debug.
- **Extraction over personal data.** The processing activity catalogue
  (`x-openregister-processing`) already declares anonymisation, OCR,
  metadata enrichment and signing. NAW extraction is a fifth activity and
  is declared with its purpose, its legal basis and its retention, not
  added quietly to an existing one.
- **Volume.** A scanning contract arrives in batches. Classification and
  extraction run as background jobs with progress on the intake record,
  and the inbox lists what is still being read rather than hiding it.
