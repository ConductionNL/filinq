# Tasks: finish-half-built-capabilities

## 1. OCR

- [ ] 1.1 Write the recognised text back as a text layer on the PDF in `lib/Service/Ocr/OcrRunService.php`, keeping the original as a file version (REQ-FHB-01). Verify: PHPUnit that a scanned PDF has extractable text after the run.
- [x] 1.2 Call `extractFromProvidedText()` with OpenRegister's `ProvidedTextRequest` in `lib/Service/Ocr/OcrExtractionFallback.php` (row det-scans, REQ-DDOCR-003). Verify: PHPUnit against a stub with the real signature; the scan is no longer `ocrDetectionPending`.

## 2. Woo consent

- [ ] 2.1 Send the consent request to the third party by letter or email from `lib/Service/ConsentService.php`, through the email leaf or the letter composer, and record when and how it went (REQ-FHB-02). Verify: PHPUnit that a request with an email address produces one message and a sent record.

## 3. Generation and finality

- [ ] 3.1 Pass `listRefs` from `lib/Service/CorrespondenceService.php` generate() and from the preview in `src/views/templates/TemplateDetail.vue` (row gen-lists, REQ-DDLR-001). Verify: PHPUnit that a template table is filled from two related records.
- [ ] 3.2 Give `POST /api/document-finality-rules` a caller: a rule section on the admin settings, or the dossiq case type configuration calling it (row wk-final-rule, REQ-FDF-04). Verify: Playwright declares a rule and a status change makes the document final.

## 4. Signing

- [ ] 4.1 Read `signingMode` and each signer's `order` in `lib/Service/SigningService.php` sign(), and refuse a sequential signer whose turn has not come (row sig-order). Verify: PHPUnit that B cannot sign before A in sequential mode and can in parallel mode.
