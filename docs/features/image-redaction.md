# Image Redaction

## Overview

Text redaction cannot reach pixels. A scanned letter, a photographed form or
an image inside a PDF keeps showing a BSN or a signature after its text layer
has been anonymised. Image redaction closes that gap without Filinq growing an
engine of its own: **OpenRegister's image seam** finds regions and burns them
into the pixels; Filinq decides what to send there, keeps every finding in the
one review model, and says honestly when image content was not looked at or
not burned.

It also adds the Woo scopes beside a single finding: a reviewer can redact
**one page**, a **range of pages** or the **whole document** in one action, as
one decision with its grounds.

## Who does what

| | OpenRegister (image seam) | Filinq |
| --- | --- | --- |
| Finding regions | `ImageRedactionService::detectImage()`: OCR word boxes and registered detectors (anonymiq for signatures, faces, number plates) | Sends image-bearing files to the seam after the OCR fallback; attaches the regions to the entity rows |
| Storing regions | `EntityRelation` rows with `page` and `box` | Shows them as rows with `origin: image` and `boxes` |
| Burning pixels | `redactImage()`: opaque fill, re-encode, metadata stripped, the `_anonymized` sibling | Builds the regions (one full page per page in scope) and reports what was burned |
| Withholding a whole document | — | No output; the anonymisation link records `withheld` and the grounds |

Filinq burns nothing itself (decision D2: the guarantees live in the engine).

## The three scopes

Open a document in the review sidebar and choose **Redact pages or document**.

- **One page** and **a range of pages** (from and to, inclusive) are sent to
  the seam as one full-page region per page (`box` 0, 0, 1, 1), so OpenRegister
  burns each page and removes its text.
- **The whole document** is withheld. No redacted copy is written, because a
  black PDF could be mistaken for a document. The anonymisation link records
  `withheld: true` with the grounds, and publication readiness lists the
  document as not for publication. The withhold also holds when the document
  is anonymised the usual way, alone or in a batch: the commit finds the
  stored decision, records the withhold and writes nothing, and the batch
  entry carries `withheld` and the grounds.

Every decision needs at least one ground. A page outside the document, or a
range that runs backwards, is refused before anything is stored, with the
reason (for example "The document has 4 pages, so pages 3 to 6 cannot be
redacted."). Each decision appears as one row under the review, with its
grounds.

Without OpenRegister's image seam, page and range scopes are refused with
"Page redaction needs OpenRegister's image seam, so nothing was written."
Withholding the whole document still works, because it writes no pixels.

## Never silent

| Situation | What Filinq reports |
| --- | --- |
| An image or a scan, and no image seam | `imageDetectionSkipped` `backend_unavailable` on extract; the review warns that the images were not checked |
| The seam could not read the file (no OCR words) | `imageDetectionSkipped` `not_detected` with the seam's reason |
| No region detector ran | `objectDetection: not-run`; the review says signature detection is not available here |
| An anonymise left image content unburned | `imageRedactionPending: true` on the run and on its anonymisation link; the review warns not to publish the copy |
| Regions were burned | `burnedRegionCount`: only the regions the seam actually burned |

## API

| Endpoint | What it does |
| --- | --- |
| `GET /api/anonymization/scope/{fileId}` | The document's scope decisions, its page count and whether page scopes can be committed |
| `POST /api/anonymization/scope/{fileId}` | Store one decision (`scope`, `fromPage`, `toPage`, `bases`) |
| `POST /api/anonymization/scope/{fileId}/commit` | Burn the pages through the seam, or withhold the document |

Each endpoint first resolves the file in the caller's own folder and answers
404 when it is not there.

## Data

- `anonymizationLink` 1.5.0: `burnedRegionCount`, `imageRedactionPending`,
  `withheld`, `withheldBases`; status `withheld`; `anonymizedFileId` is no
  longer required, since a withheld document has no output.
- `documentReview` 1.1.0: `scopeDecisions`, each with `scope`, `fromPage`,
  `toPage`, `bases`, `decidedBy` and `decidedAt`.

No stored field holds region pixels or entity values.

## Not built yet

- Region overlays on the workbench preview, and the burned-region count in the
  file listings.
- A visible notice on a burned page naming its ground: OpenRegister's seam has
  no notice yet.
- Burning born-digital PDF pages: OpenRegister's seam burns standalone images,
  office media and the page image of a scanned PDF page. A PDF page drawn as
  text and vector graphics has no single image to burn, so a page scope on it
  is refused with OpenRegister's reason and nothing is written. Filinq does not
  rasterise the page itself; that waits for OpenRegister.
- Burning the page scopes during the usual anonymise run: today they are
  committed on their own (`POST /api/anonymization/scope/{fileId}/commit`).
- Screenshots for this page (captured by the Playwright journey on a running
  instance).

## Specification

`openspec/changes/image-redaction/` (REQ-DDIMR-001 to 009).
