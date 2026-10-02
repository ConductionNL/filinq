---
id: final-documents
title: Final documents
sidebar_label: Final documents
sidebar_position: 14
description: Mark a document final so it stops changing, and correct it by superseding rather than rewriting
keywords:
  - final
  - frozen
  - besluit
  - archiefwet
  - supersede
  - unfreeze
---

# 🔒 Final documents

## Overview

A besluit that can still be edited after it is taken is a besluit nobody can
prove the contents of. The Archiefwet and a vastgesteld besluit both require
that what was published stays what was published, and the proof is what the
archive is for.

Mark a document final and it stops changing. Filinq records who made it final,
when, why, and the checksum of the file at that moment. Every write path then
refuses it with a sentence that names all four, so a handler who tries to edit
it learns why instead of filing a support ticket.

Correcting a final document does not edit it. It produces a new document that
says which one it supersedes, and the superseded one stays readable and stays
final. That is the difference between a correction and a rewrite, and it is what
makes the correction explicable in 2029.

## What you do

### Make a document final

1. Open the document and go to the **Versions** tab.
2. Choose **Make final** and say what makes it final, such as the decision that
   was taken.
3. The panel at the top of the tab now names you, the moment and the reason.
   **Restore** disappears, because the endpoint behind it would refuse.

### Correct a final document

1. On the **Versions** tab of the final document, choose **Issue a correction**.
2. Say what the correction changes.
3. Filinq writes the correction beside the original and links the two. Open
   either one and the panel points at the other.

The original file is never opened for writing, so nothing about it can be lost
by correcting it.

### Unfreeze, when you have to

An administrator can unfreeze a final document. It needs the
`docudesk-final-document-admins` right and a reason, and it leaves a mark on the
document that nobody can clear.

The exception exists on purpose. Without one, somebody edits the file on the
filesystem and there is no record at all. With a silent one, the guarantee is
worthless. So it is allowed, recorded, and visible to the next person who opens
the document.

## Letting the decision do the freezing

A consuming app declares which of its record-type states make a document final,
and Filinq applies the declaration when the state changes. Dossiq declares, per
case type, that the "besluit genomen" status makes its besluit final; the
besluit then freezes because the decision was taken, not because somebody
remembered to press a button.

Filinq does not define the record type vocabulary. Case types and result types
belong to the consuming app, and Filinq stores only what that app declared.

```http
POST /apps/filinq/api/document-finality-rules
{
  "declaringApp": "dossiq",
  "typeReference": "bezwaarschrift",
  "finalStates": ["besluit genomen"],
  "documentRole": "besluit"
}
```

```http
POST /apps/filinq/api/document-finality-rules/apply
{
  "declaringApp": "dossiq",
  "typeReference": "bezwaarschrift",
  "state": "besluit genomen",
  "documentRole": "besluit",
  "fileIds": [4711]
}
```

A record type with no declaration freezes nothing.

## What freezing does not do

Freezing the record does not freeze the filesystem. Somebody with shell access
can still change the file, and Filinq reports that rather than preventing it:
the version records the file's checksum at the moment it became final, and the
**Versions** tab says so when the bytes no longer match. That is detection, not
prevention.

Nor is it a rights management system. A frozen document is a refusal to change
the record, not a way to stop somebody copying a file out of Nextcloud.

## Where it sits

| Behaviour | Owner |
| --- | --- |
| The draft to final lifecycle, its audit entry and the refusal of a way back | OpenRegister, `x-openregister-lifecycle` on `documentVersion` |
| The refusal on every write path | Filinq, `FinalDocumentService` |
| The version history itself | Nextcloud `files_versions`, read through [Document Versions](./document-versions.md) |
| Retention and destruction after freezing | OpenRegister, archival and destruction workflow |
| Freezing a case, a phase or a message | OpenRegister, cluster 29 |

## Next

Read [Document Versions](./document-versions.md) for the version history a final
document is frozen against, and [Digital Signing](./digital-signing.md) for the
signature that is a common reason a version becomes final.
