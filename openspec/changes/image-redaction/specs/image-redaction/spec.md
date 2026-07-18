# image-redaction Specification (delta)

---
status: proposed
---

## Purpose

Detect PII inside image content — image files, scanned-PDF pages, images
embedded in PDFs — through OpenRegister's anonymisation backend chain, review
the detected regions in the wave-1 review workbench, and redact by
irreversibly burning pixels. Adds `SIGNATURE` (handwritten signature) to the
entity taxonomy as a reviewable, maskable entity type. The engine (detection
and per-image burn) lives OpenRegister-side; DocuDesk owns image acquisition,
container reassembly, review surfacing and honest degradation.

## ADDED Requirements

### Requirement: Image PII detection runs through the OpenRegister image seam (REQ-DDIMR-001)

Image content MUST be submitted for entity detection through an OpenRegister
image-detection seam on the anonymisation backend chain (Presidio image mode
as the first supported backend), returning detected entities typed by the
shared taxonomy (PERSON, ORGANIZATION, EMAIL, IBAN, …, SIGNATURE) with
per-page bounding boxes normalised to [0..1]. DocuDesk MUST submit: (a) files
with an image MIME type, (b) page rasters of scanned PDFs (rasterised via
the existing Imagick path at the configured OCR DPI, one shared raster per
page for detection and burn), and (c) images extracted from the image
XObjects of born-digital PDFs (in v1 — decision D1). When a specific embedded
XObject cannot be decoded, that file MUST flag `imageDetectionSkipped` reason
`embedded_images_unsupported` (per-file honest degradation), never a silent
skip of the whole document. DocuDesk MUST NOT implement or embed its own
image-detection engine (ADR-017/ADR-022) and MUST NOT send image content to
any endpoint other than the OpenRegister seam (processing stays local,
AVG/EDPB posture).

#### Scenario: Scanned page yields image entities with boxes

- GIVEN an image-capable backend configured in OpenRegister and a scanned Demostad letter containing a printed BSN
- WHEN extraction runs for the anonymisation flow
- THEN the consolidated entity list contains the BSN entity with `origin: "image"` and at least one normalised bounding box carrying the page number
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

#### Scenario: Image file is submitted as-is

- GIVEN a PNG photo of an identity document uploaded to the anonymisation flow
- WHEN extraction runs
- THEN the file bytes are submitted to the OpenRegister image seam without rasterisation
- AND detected entities carry boxes without a page number
- @e2e exclude backend submission plumbing — covered by PHPUnit (tests/unit/Service/ImageRedactionServiceTest.php)

#### Scenario: Born-digital PDF with an embedded image is reached

- GIVEN a born-digital PDF whose page carries a photo as an image XObject (no scanned page raster) and a printed BSN inside that photo
- WHEN extraction runs with an image-capable backend
- THEN the embedded image is extracted and submitted to the OpenRegister seam
- AND the BSN entity is returned with `origin: "image"` and a bounding box referencing that page
- @e2e exclude embedded-XObject extraction path — covered by PHPUnit (tests/unit/Service/ImageRedactionServiceTest.php)

#### Scenario: Undecodable embedded image flags the file honestly

- GIVEN a born-digital PDF whose embedded image XObject cannot be decoded
- WHEN extraction runs
- THEN that file carries `imageDetectionSkipped` with reason `embedded_images_unsupported`
- AND the review workbench shows the image-not-scanned warning for the document
- @e2e exclude degradation-reason branch — covered by PHPUnit (tests/unit/Service/ImageRedactionServiceTest.php)

### Requirement: Missing image capability degrades fail-flagged, never fail-silent (REQ-DDIMR-002)

Extraction of image-bearing content MUST mark the file
`imageDetectionSkipped` with a machine-readable reason in the extract
response when no image-capable backend is available (seam absent at runtime,
backend unreachable, or `supportsImages` false), and the review UI MUST show
a warning that image content was not scanned for entities. The file MUST NOT
be reported as reviewed-clean on the basis of text-only detection, and an
anonymise run over content with unscanned or unburnable image regions MUST
set `imageRedactionPending: true` on the `anonymizationLink` record. The
degradation contract mirrors `ocr-trigger-surface` REQ-DDOCR-004.

#### Scenario: Seam absent flags the scan instead of passing it

- GIVEN an OpenRegister version without the image seam and a scanned PDF in the anonymisation flow
- WHEN extraction runs
- THEN the extract response carries `imageDetectionSkipped` with reason `backend_unavailable`
- AND the review workbench shows the image-not-scanned warning for the document
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

#### Scenario: Anonymise with pending image regions is marked, not silently clean

- GIVEN a scanned file whose image detection was skipped
- WHEN the operator commits anonymisation anyway
- THEN the run's `anonymizationLink` records `imageRedactionPending: true`
- AND the result listing shows the pending-image warning instead of an unqualified success state
- @e2e exclude flag persistence and listing derivation — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: Handwritten signatures are detected as entity type SIGNATURE (REQ-DDIMR-003)

Handwritten signatures MUST be modelled as entity type `SIGNATURE` in the
shared entity taxonomy (OpenRegister-side constant, sibling of PERSON and
ORGANIZATION), detected by an image-capable backend that declares
`supportsSignatures`, and surfaced in review exactly like other entities
(a signature is personal data — AVG Art. 4(1); masking it is standard in all
nine Dutch algoritmeregister anonymisation entries). The detection engine is
OpenRegister-side and configurable; DocuDesk MUST NOT implement signature
detection itself. When the configured backend does not declare
`supportsSignatures`, the review UI MUST state that signature detection is
unavailable on this instance — an empty result MUST NOT imply "no
signatures".

#### Scenario: Signature block is detected, reviewed and maskable

- GIVEN a signature-capable image backend and a scanned letter with a handwritten signature
- WHEN extraction and review run
- THEN the entity list contains a `SIGNATURE` entity with `origin: "image"` and a bounding box
- AND the reviewer can accept it for redaction like any other entity
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

#### Scenario: No signature-capable backend is stated honestly

- GIVEN an image backend with `supportsSignatures: false`
- WHEN the review workbench renders a scanned document
- THEN it shows that signature detection is not available on this instance
- AND no `SIGNATURE` rows are fabricated
- @e2e exclude capability-flag rendering branch — covered by PHPUnit (tests/unit/Service/ImageRedactionServiceTest.php) and Vitest on the workbench state

### Requirement: Redaction burns pixels irreversibly (REQ-DDIMR-004)

Committing an accepted image-region redaction MUST paint the region opaque in
the raster and re-encode the image, replacing the original image object or
page raster in the anonymised output so the source pixels are absent from the
output file (Arnhem "onleesbaar"). The burn MUST NOT be implemented as an
annotation, overlay, optional-content group, or any drawing on top of intact
image data. Text-layer content and OpenRegister chunk text covering a burned
region MUST be removed/redacted in the same commit (a scanned PDF's wave-1
OCR text layer would otherwise still carry the value). Before reporting
success, the output MUST be verified to contain neither the original image
stream nor overlay-style redaction constructs; a failed verification MUST
fail the run with a structured error — never return the unverified artifact.
The per-image burn is executed by the OpenRegister seam's redact operation;
DocuDesk performs container reassembly only.

#### Scenario: Burned region is unrecoverable in the output

- GIVEN an accepted BSN region on a scanned page
- WHEN anonymisation commits
- THEN the output page raster shows an opaque block over the region
- AND the output file does not contain the original page-image stream
- AND extracting text from the output yields no BSN value for that region
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

#### Scenario: Overlay-style output is rejected by verification

- GIVEN a burn result that still references the original image stream under an opaque rectangle
- WHEN the irreversibility verification runs
- THEN the anonymise run fails with a structured error naming the verification failure
- AND no output file is reported as anonymised
- @e2e exclude structural verification guard — covered by PHPUnit (tests/unit/Service/ImageRedactionServiceTest.php)

### Requirement: Image regions review through the existing workbench model (REQ-DDIMR-005)

Image-origin detections MUST appear as rows in the same consolidated entity
decision model the review workbench uses (`anonymization-review-workbench`
REQ-DDARW-002 — one shared model, no forked state), carrying `origin:
"image"` and their boxes, and MUST render as positioned overlays on the
workbench preview panes. Accept/reject/skip/grondslag decisions, prohibition
and standing-consent pre-application, and the per-document checked gate
(REQ-DDARW-007/008) MUST apply to image entities unchanged. A missed or
unrenderable overlay MUST NOT hide the entity from review — the table remains
the source of truth.

#### Scenario: Region overlay and table row are the same decision

- GIVEN a reviewed scan with a detected image-origin PERSON entity
- WHEN the reviewer rejects the entity in the table
- THEN the preview overlay for that region reflects the rejected state
- AND committing anonymisation does not burn that region
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

#### Scenario: Checked gate covers image entities

- GIVEN a document with image-origin detections and no valid `documentReview`
- WHEN anonymise-commit is attempted with the checked gate enforced
- THEN the commit is rejected exactly as for text-origin entities (HTTP 409)
- @e2e exclude gate reuse without modification — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

### Requirement: Burn runs are honestly reported (REQ-DDIMR-006)

Every anonymise run MUST record on its `anonymizationLink` the number of
image regions burned (`burnedRegionCount`, additive field) and whether image
redaction is pending (`imageRedactionPending`), and these MUST be reflected
in file listings and the run evidence consumed by the processing certificate
(`document-waarmerk-certification` REQ-DDWMK-003 reports counts per entity
type — SIGNATURE and image-origin counts ride the same mechanism). Reported
counts MUST derive from performed burns, never from requested regions (the
GH #286 fabricated-count class). No stored field or report SHALL contain
region pixel content or entity values (AVG Art. 5(1)(c)).

#### Scenario: Burn count reflects performed burns only

- GIVEN three accepted regions of which one fails to burn
- WHEN the run completes
- THEN the run fails or records `burnedRegionCount: 2` with `imageRedactionPending: true` per the failure contract
- AND the count is never reported as 3
- @e2e exclude count derivation and failure accounting — covered by PHPUnit (tests/unit/Service/ImageRedactionServiceTest.php)

#### Scenario: Listings surface the image-redaction state

- GIVEN a completed run with burned regions
- WHEN the operator opens the file listing
- THEN the document shows its burned-region count alongside the replacement count
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts
