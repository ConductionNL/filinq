# Tasks: documents-from-a-template

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 11. -->

## 1. Schemas

- [x] 1.1 Add `pageLayout` (paper, margins, header, footer, logo, first-page difference, version) to the `filinq` register, with an authorization cascade and a descriptor version bump (REQ-DFT-01)
- [x] 1.2 Add `periodicDocument` (view slug, template, layout, cadence, last run) and `archiveJob` (inputs, ceiling, manifest, lifecycle) to the same register (REQ-DFT-02, REQ-DFT-03)

## 2. Layout

- [x] 2.1 A template names a layout version; `PdfService` renders through it, and the generated document records both versions (REQ-DFT-01)
- [x] 2.2 Admin surface to author and version a layout, with a preview of the first and following pages (REQ-DFT-01)
  - Built 2026-09-28: `src/views/settings/PageLayoutSettings.vue` on the admin settings page lists the active layouts, creates one (`POST api/page-layouts/new`, `PageLayoutService::create()`), saves an edit as the next version and lists the versions. The preview draws the first and a following page from the form (header, footer, margins, orientation); it is a drawing of the layout, not a PDF render. Tests: `PageLayoutServiceTest` (active list, create, refusals, the written payload against the register), `DocumentProductionControllerContractTest`, `tests/vitest/pageLayoutForm.spec.js`.

## 3. The archive

- [x] 3.1 `CaseArchiveService`: collect every file on an object, honour the administered ceiling, write the manifest of what was included and what was left out with the reason (REQ-DFT-02)
- [x] 3.2 Leaf `filinq-download-all-files` per ADR-066, warning before the job starts when the selection already exceeds the ceiling (REQ-DFT-02)
  - Built 2026-09-28: `RegisterDownloadAllFilesLeafListener` and `src/integrations/registerDownloadAllFilesLeaf.js` (surfaces `detail-page`, `single-entity`), widget `CnFilinqDownloadAllFilesWidget.vue` reads the preflight, warns when `exceedsCeiling`, builds the archive and lists every left-out file by reason. Tests: `RegisterDownloadAllFilesLeafListenerTest`, `tests/vitest/archiveBundle.spec.js`.

## 4. Periodic and released documents

- [x] 4.1 Render a template over the records a saved view returns, on a cadence, writing a new generated document each run and never editing the previous one (REQ-DFT-03)
  - Correction 2026-09-28: the run writes a record but renders no file, and no job runs it on its cadence. Both halves are carried by the change `periodic-documents-on-a-schedule`.
- [x] 4.2 `reviewInterval` on a released document computes a review date, lists the document as due and notifies its owner through the notification dialect (REQ-DFT-04)
- [x] 4.3 Moved on 2026-09-28 to the change `forms-as-documents`, with REQ-DFT-05. It needs a form surface, which filinq does not have; see that change's open question.

## 5. Field values and trust

- [x] 5.1 Moved on 2026-09-28 to the change `forms-as-documents`, with REQ-DFT-05. It needs a form surface, which filinq does not have; see that change's open question.
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

**Closed 2026-09-28:** 2.2 and 3.2 are built; 4.3 and 5.1 moved with REQ-DFT-05 to `forms-as-documents`.
