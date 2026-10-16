---
id: template-version-provenance
title: Which template version made a document
sidebar_label: Template version of a document
description: A generated document names the template version it was rendered from, a caller can render an older version again, and filinq answers which version was in force on a date.
keywords:
  - template
  - version
  - generation
  - beschikking
---

# Which template version made a document

A caseworker who defends a beschikking at a bezwaarcommissie has to show the letter that was sent and the template it came from. Filinq now records that version on every generated document, and can render the same version again after the template has moved on.

## The version number

A template's version is the number in filinq's own version chain. Every edit stores the old content as a snapshot, so a template with three snapshots is on version 4. `TemplateService::getTemplate()` returns that number as `version`.

We chose the chain over OpenRegister's `@self.version` on purpose. That value is a string such as `0.0.7` and it also moves on saves that make no template version, such as a lock release. The chain is the number you can pin.

When the chain cannot be read, `version` is left out. An unknown version is never reported as 1.

## Render a specific version

Pass `options.templateVersion` to `POST /api/documents/generate` (or to `DocumentService::generateDocument()`):

```json
{
  "templateId": "7f3c...",
  "dataRefs": [{ "register": "zaken", "schema": "zaak", "id": "a1b2..." }],
  "options": { "format": "pdf", "templateVersion": 2 }
}
```

Filinq renders the content stored for version 2. The template itself does not change: this is not a restore.

A version the chain does not hold fails with a 404 that names the template and the version. Filinq does not fall back to the current version, because that document would claim a version it was not made from. `options.templateVersion` together with `options.formats` is refused with a 400 for now; generate one format at a time.

## What the result tells you

| Field | Meaning |
|---|---|
| `templateVersion` | The version that was rendered, or `null` when unknown. The `generatedDocument` record stores the same number, and leaves it out when unknown. |
| `sha256` | A SHA-256 over the bytes filinq produced. |
| `pageCount` | The number of pages, for a PDF only. DOCX, ODT and HTML carry no `pageCount` at all. |

## Which version was in force on a date

`TemplateVersionService::versionInForceAt($templateId, $moment)` answers from the chain's own timestamps. Version 1 starts when the template is created; each later version starts when the previous one was replaced. A moment before the template existed returns `null`, not the oldest version.

These are edit times, not effective dates. A template edited on 1 June to apply from 1 July answers 1 June.

## Next step

Regenerating a letter for an appeal? Read `templateVersion` from its `generatedDocument` record and pass it back as `options.templateVersion`.
