# Sanitize documents

Remove what a Word or OpenDocument file hides before you publish or share it: comments, tracked changes, author names in the properties, field codes and hidden data parts.

Sanitizing never changes the original. Filinq saves a clean copy next to it, named `<name>_sanitized.<ext>`, and keeps a record of how many items it removed per category. The record holds counts only, never the removed comments, names or text.

## Sanitize a file

1. Open **My documents**.
2. Open the actions of a file and click **Sanitize**.
3. The panel shows where the clean copy went and what was removed, per category.

| Answer | Meaning |
|---|---|
| Clean copy saved | The copy is next to the original; the counts are in the panel |
| This PDF was not sanitized | OpenRegister cannot clean PDFs yet. Nothing was changed and nothing claims the PDF is clean |
| This document is encrypted | Remove the password and try again |
| Only Word and OpenDocument text files can be sanitized | Other formats have nothing Filinq can clean yet |

The work is done by OpenRegister's office sanitizer. Nothing leaves the server.

## Anonymisation keeps the report too

OpenRegister cleans every Word or OpenDocument file it anonymises. The anonymisation result now shows what it removed, and Filinq keeps the same record for the anonymised copy. When the anonymised copy is delivered as a PDF, the report is shown but no record calls that PDF sanitized: the conversion writes new metadata.

## Before you hand off for publication

The Woo hand-off warns when the file to publish is not a sanitized copy. The warning does not block the hand-off. Sanitize the file in **My documents** first, then publish the clean copy.

## What is not there yet

- PDFs: waiting for a PDF sanitizer in OpenRegister.
- The `sanitize` option on anonymisation, which would clean the final PDF: it needs that same PDF sanitizer.
- The warning when you sanitize a file that carries a waarmerk: it comes with the waarmerk.

## API

| Route | What it does |
|---|---|
| `POST /apps/filinq/api/sanitization/{fileId}` | Sanitize a file you can open; answers the clean copy and the counts, or `sanitizationSkipped` with a reason |
| `GET /apps/filinq/api/sanitization/{fileId}` | Earlier runs on the file, and whether the file itself is a sanitized copy |

A file you cannot open answers 404.
