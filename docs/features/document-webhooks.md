---
id: document-webhooks
title: Tell an outside system what happened to a document
sidebar_label: Document webhooks
description: Filinq raises four document events (signed, anonymised, generated, registered) that OpenRegister delivers to a receiving URL you set up in the admin settings.
keywords:
  - webhooks
  - events
  - integration
---

# Tell an outside system what happened to a document

A records system wants to know when a contract comes back signed. Instead of
polling, it gets a call. Filinq raises one event when a document is signed,
anonymised, generated or registered, and OpenRegister delivers it to every
receiver that asked for it: it queues the call, retries, signs it with the
receiver's secret and keeps the delivery log. Filinq itself sends nothing.

The event is raised from the record, not from the screen, so a signing request
completed through OpenRegister's API, the portal or an agent raises the same
event as a click in filinq. A failed generation raises nothing.

## Setting up a receiver

As an admin, open filinq's admin settings and go to **Document webhooks**.

1. Type a name and the receiving URL.
2. Tick one or more events: *Document signed*, *Document anonymised*,
   *Document generated*, *Document registered*.
3. Type a secret. The form does not save without one: the receiver uses it to
   check that a delivery really came from this server.
4. Add the receiver, then **Send a test**. The row shows whether the last
   delivery arrived.

The receiver is an ordinary OpenRegister webhook, so it is also listed on
OpenRegister's own webhook page. When the OpenRegister on the server cannot
deliver filinq's events yet, the section says *Delivery is not available on
this server* and still lists the saved receivers.

To subscribe from OpenRegister directly, name the event class:

| Event | Class |
|---|---|
| `document.signed` | `OCA\Filinq\Event\Webhook\DocumentSignedEvent` |
| `document.anonymised` | `OCA\Filinq\Event\Webhook\DocumentAnonymisedEvent` |
| `document.generated` | `OCA\Filinq\Event\Webhook\DocumentGeneratedEvent` |
| `document.registered` | `OCA\Filinq\Event\Webhook\DocumentRegisteredEvent` |

`OCA\Filinq\Event\Webhook\*` matches all four.

## What each event sends

Every event names the document and the record, never the file's content. It
carries no file bytes, no entities found in the text, no signer e-mail
addresses and no IP addresses. The receiver fetches the file itself, with its
own credentials, so Nextcloud's permissions still apply. A file name can hold
personal data: choose the receiver with that in mind.

Every envelope holds `event`, `occurredAt`, `document` (`fileId`, `name`),
`record` (`register`, `schema`, `id`) and `actor` (the user, or `null` for a
system write), plus the event's own fields.

### document.signed

```json
{
  "event": "document.signed",
  "occurredAt": "2026-11-13T09:30:00+00:00",
  "document": { "fileId": 501, "name": "contract.pdf" },
  "record": { "register": "4", "schema": "9", "id": "rec-1" },
  "actor": "signer-2",
  "signingRequestId": "rec-1",
  "signedFileRef": "777:3",
  "signatureLevel": "QES"
}
```

### document.anonymised

```json
{
  "event": "document.anonymised",
  "occurredAt": "2026-11-13T09:30:00+00:00",
  "document": { "fileId": 10, "name": "besluit.pdf" },
  "record": { "register": "4", "schema": "9", "id": "rec-1" },
  "actor": "clerk",
  "sourceFileId": 10,
  "anonymisedFileId": 11,
  "verificationVerdict": "clean"
}
```

### document.generated

```json
{
  "event": "document.generated",
  "occurredAt": "2026-11-13T09:30:00+00:00",
  "document": { "fileId": 88, "name": "brief.pdf" },
  "record": { "register": "4", "schema": "9", "id": "rec-1" },
  "actor": null,
  "templateId": "tpl-7",
  "templateVersion": 2,
  "format": "pdf"
}
```

### document.registered

`document.fileId` is the registered document's reference as the registration
holds it.

```json
{
  "event": "document.registered",
  "occurredAt": "2026-11-13T09:30:00+00:00",
  "document": { "fileId": "doc-uuid-5", "name": null },
  "record": { "register": "4", "schema": "9", "id": "rec-1" },
  "actor": "clerk",
  "direction": "outbound",
  "unit": "juridische-zaken",
  "registrationNumber": "UIT-2026-0042"
}
```
