# anonymization Specification (delta)

---
status: proposed
---

## Purpose

Extend the anonymisation pipeline with image awareness: the extract response
carries image-origin entities with region geometry (additive), and the
anonymise path burns accepted image regions before output conversion, with a
fail-flagged contract when the image path cannot run. Existing requirements
(REQ-ANON-00..10 and the outputFormat/prohibition-gate requirements) are
unchanged; this delta only ADDs requirements. Detection and per-image burning
are delegated to OpenRegister's backend chain (`AnonymisationBackendService`
image seam); DocuDesk orchestrates and reassembles.

## ADDED Requirements

### Requirement: The extract response carries image-origin entities with region geometry (REQ-DDIMR-007)

The extract endpoint's consolidated entity entries MUST be extended,
additively and non-breaking, with `origin` (`"text"` default | `"image"`)
and, for image-origin entities, `boxes` (a list of normalised bounding boxes
`{page?, x, y, w, h}` in [0..1]). Pre-change consumers that ignore the new
fields MUST see identical behaviour for text-origin entities. Image-origin
entities MUST flow through the SAME post-processing as text entities:
grondslag proposals, prohibition/standing-consent matching, and risk-level
computation (one pipeline, no image side-channel).

#### Scenario: Pre-change client is unaffected

- GIVEN a born-digital PDF with only text-origin detections
- WHEN extraction runs
- THEN every entity carries `origin: "text"` and no `boxes` field
- AND the response is otherwise byte-compatible with the pre-change shape
- @e2e exclude additive-compat contract — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)

#### Scenario: Image entities receive proposals and policy matches

- GIVEN a scanned document whose image detection finds a PERSON entity matching an active publication prohibition
- WHEN extraction completes
- THEN the image-origin entity carries the grondslag proposal for PERSON and the `prohibitionMatch` flag
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

### Requirement: Anonymise burns accepted image regions before output conversion (REQ-DDIMR-008)

The anonymise path MUST, for accepted image-origin entities, execute the
pixel burn (via the OpenRegister image seam, `image-redaction`
REQ-DDIMR-004) on the affected image objects/page rasters BEFORE the
`outputFormat` PDF-conversion gate, so the conversion cascade and any
grondslagen-summary append operate on the already-burned artifact. Text-side
replacement via OpenRegister's `FileService::anonymizeDocument` MUST remain
in place for text-origin entities of the same document (burn composes with
it, never replaces it). When any accepted image region cannot be burned, the
run MUST either fail with a structured error or complete with
`imageRedactionPending: true` recorded on the `anonymizationLink` — it MUST
NOT report an unqualified success while PII pixels remain readable.

#### Scenario: Mixed document burns regions and replaces text in one run

- GIVEN a scanned attachment page (image BSN region) inside a document that also has text-origin entities
- WHEN anonymisation commits with `outputFormat: "pdf"`
- THEN the output PDF contains the burned page raster and the text replacements
- AND the burn happened before PDF conversion (the converted output embeds the burned raster)
- @e2e tests/e2e/spec-coverage/image-redaction.spec.ts

#### Scenario: Unburnable region never yields a silent success

- GIVEN an accepted image region and an image backend that fails during the burn
- WHEN anonymisation runs
- THEN the response is a structured error, or a completed run whose `anonymizationLink` carries `imageRedactionPending: true`
- AND no response reports the document as fully anonymised
- @e2e exclude failure-contract branch — covered by PHPUnit (tests/unit/Service/AnonymizationServiceTest.php)
