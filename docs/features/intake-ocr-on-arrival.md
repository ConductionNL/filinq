---
id: intake-ocr-on-arrival
title: Reading scans on arrival
sidebar_label: Reading scans on arrival
description: Filinq reads the text of a scan or photo that lands in the intake inbox, without anyone starting it.
keywords:
  - OCR
  - intake
  - inbox
  - scan
---

# Reading scans on arrival

A scanned letter or a photo that lands in the intake inbox is read in the background. Nobody has to start it. The text is kept on the intake document, so the inbox search finds the letter by a word in it and the classifier can read it.

## What the clerk sees

The inbox has a **Text** column:

| Label | Meaning |
|---|---|
| Waiting to be read | The document arrived and is in the queue. |
| Reading | The background job is reading it now. |
| Text recognised | The text is on the document. |
| Text could not be read | Hover to see why: OCR is off or not installed, the file has no text, or it could not be found. |

A document that is not a scan or a photo shows nothing in that column. A document whose text could not be read stays in the inbox and can be assigned by hand.

## How it works

Every channel (scanner folder, mail, digital post) comes through the same door. When a new document with a file arrives and the file is an image or a PDF, filinq marks it *Waiting to be read* and queues a background job. The job runs Tesseract on the server with the admin's OCR languages and resolution, and stores the text in `contentText` on the intake document. A second delivery of the same document is not read again.

## The setting

**Read scans on arrival**, under OCR in the Filinq admin settings, is on by default. It does nothing when OCR is off or Tesseract is missing: the document then says *Text could not be read* instead of waiting forever. Switch it off to read nothing on arrival; **Run OCR** on a single file still works (see OCR document scanning).
