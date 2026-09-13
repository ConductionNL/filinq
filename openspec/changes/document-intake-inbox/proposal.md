---
kind: code
---

# Proposal: document-intake-inbox

## Why

A municipality receives documents three ways before anyone knows which case
they belong to: a scanner, a shared mailbox and digital post. OpenCase and
Zaaksysteem both put those documents in one inbox where a clerk assigns each
one to a case or rejects it. dossiq has no such inbox. Its `InboundEmailJob`
links tagged mail to an existing case and drops everything else (dossiq round 2,
finding B03, M1 1.4).

The fleet plan puts document intake with filinq, not dossiq (dossiq PLAN.md
section 3). So the inbox is a filinq surface. dossiq only renders it on its
own pages and receives the link once a clerk assigns a document to a case.

Competitor evidence, in the dossiq analysis at
`concurrentie-analyse/procest/_round2/`: `oc/pages/IncomingDocuments.md`,
`oc/pages/Configuration-IncomingDocuments.md` (OpenCase), `zs/pages/Documentintake.md`
(Zaaksysteem), `gz/code-census.md` (GZAC inbox CloudEvents consumer). Decision
D11 in `decisions.md` asks each sibling app to write its half now.

## What changes

- A new `intakeDocument` schema in the `document` register: one object per
  document waiting for a case, with its channel (`scan`, `mail`, `digitalPost`),
  the received file, sender metadata and a lifecycle `received`, `assigned`,
  `rejected`.
- An intake inbox page in filinq that lists waiting documents, previews one, and
  lets a clerk assign it to an object in any register or reject it with a reason.
- A leaf per ADR-066, kind `render-surface`, id `filinq-document-intake`. dossiq
  places the leaf on its Documents page and its case detail page. The leaf
  renders the inbox filtered to the case type or the case at hand. Assigning
  writes the link on the intake document; dossiq picks it up through its
  existing `zaakinformatieobject` relation. The leaf carries no verb into
  dossiq.
- Channel feeders stay elsewhere: mail arrives through integriq's
  `mail-intake-creates-cases`, digital post through integriq's
  `berichtenbox-digital-post-adapter`, scans through the existing OCR folder
  watch. Each feeder creates an `intakeDocument`; none of them assigns.

## Out of scope

- Separation sheets and QR cover sheets for paper scanning (C08). Only if
  filinq wants paper intake later.
- Creating a case from the inbox. That is a dossiq action on the object the
  leaf hands back, not a filinq verb.
