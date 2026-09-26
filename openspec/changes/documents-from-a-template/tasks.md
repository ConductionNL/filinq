# Tasks: documents-from-a-template

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Schemas

- [x] 1.1 Add `pageLayout` (paper, margins, header, footer, logo, first-page difference, version) to the `filinq` register, with an authorization cascade and a descriptor version bump (REQ-DFT-01)
- [x] 1.2 Add `periodicDocument` (view slug, template, layout, cadence, last run) and `archiveJob` (inputs, ceiling, manifest, lifecycle) to the same register (REQ-DFT-02, REQ-DFT-03)

## 2. Layout

- [x] 2.1 A template names a layout version; `PdfService` renders through it, and the generated document records both versions (REQ-DFT-01)
- [ ] 2.2 Admin surface to author and version a layout, with a preview of the first and following pages (REQ-DFT-01)
  - The layout is authored through the endpoints and the register today; the admin surface with the two-page preview is still open.

## 3. The archive

- [x] 3.1 `CaseArchiveService`: collect every file on an object, honour the administered ceiling, write the manifest of what was included and what was left out with the reason (REQ-DFT-02)
- [ ] 3.2 Leaf `filinq-download-all-files` per ADR-066, warning before the job starts when the selection already exceeds the ceiling (REQ-DFT-02)
  - The manifest and the ceiling are endpoints. Filinq ships no leaf infrastructure yet, so the leaf is a change of its own.

## 4. Periodic and released documents

- [x] 4.1 Render a template over the records a saved view returns, on a cadence, writing a new generated document each run and never editing the previous one (REQ-DFT-03)
- [x] 4.2 `reviewInterval` on a released document computes a review date, lists the document as due and notifies its owner through the notification dialect (REQ-DFT-04)
- [ ] 4.3 A submitted form is rendered to a document at submission, from the values as submitted, and filed on the record (REQ-DFT-05)
  - Form rendering at submission belongs with the form surface and is untouched here.

## 5. Field values and trust

- [ ] 5.1 A form field holds a reference to a document record, validated as resolvable and readable by the caller; no file is copied into the field (REQ-DFT-05)
  - Untouched: a document reference as a field value belongs with the form surface.
- [x] 5.2 The verification result states which signature was checked, against which key, when, and what that proves (REQ-DFT-06)
  - Done in `SigningVerificationService`, which is where the signing services are.

## 6. Quality

- [x] 6.1 PHPUnit inside the container for the layout, the ceiling, the manifest, the schedule and the review date; 75% on new code (ADR-009)
- [x] 6.2 Playwright `tests/e2e/workflows/documents-from-a-template.spec.ts` covers the layout versioning, the manifest, the missing view and the review list; the strings and the feature docs are still open

## Status of 5.2, 2026-09-18

**Measured first.** The rest of this change is landed (#1115). `tasks.md` had
five open items; four of them are a frontend admin surface (2.2), a leaf
(3.2), a form-submission render (4.3) and a form field type (5.1) — none of
them this repo's PHP. 5.2 was, and it is the row this change is scored on: *a
cryptographic signature is verified and the signer's trust is SHOWN*.

The verification itself was already right and fail-closed. What it answered
with was a slug: `mac-mismatch`, `legacy-assertion-v1`. A slug on a screen
tells a person nothing they can act on, and it is the identifier-rendered-where-
prose-was-expected mistake in the place where it costs most.

Each signature now also carries:

- **`checked`** — what the check covered: the document bytes and the assertion
  fields, or nothing.
- **`keyId`** — WHICH key it ran against. "Verified" is meaningless without it:
  an instance whose secret was rotated reports `invalid` for every older
  artifact, and with no key id that reads as mass tampering rather than as
  "these were signed with the previous key". The id is an HMAC of a fixed label
  under the secret, truncated — it changes when the secret changes, identifies
  nothing else, and cannot be turned back into the secret.
- **`means`** — a sentence, including what a green tick does NOT mean. A MAC
  this server can recompute proves the server produced the artifact and that
  the bytes have not changed since; **it does not prove who the signer is, and
  it is not a qualified electronic signature.** A verification screen that
  leaves that out is one somebody will rely on in a dispute.

The unverifiable cases say so too, and say that they are not evidence of
anything being wrong: no key configured, a pre-v2 assertion, and a signature
this instance did not produce.

**Still open on this change:** 2.2, 3.2, 4.3 and 5.1, all surfaces rather than
services, and none of them in this repo's PHP.
