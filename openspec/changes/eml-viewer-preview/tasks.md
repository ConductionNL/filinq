# Tasks — EML Viewer Preview & Upload Acceptance

Server-rendered original-EML preview reusing the `eml-pdf-assembly` pipeline (empty entity set = no redaction), plus accepting EML in the anonymisation upload widget.

## Implementation

- [x] 1. Accept EML in the upload widget: add `message/rfc822` / `.eml` to the `accept` attribute and to `partitionFiles`' `ALLOWED_MIMES` / `ALLOWED_EXTENSIONS` in `src/views/anonymization/AnonymizationWidget.vue`.
- [x] 2. Create `lib/Service/EmlPreviewService.php` with `renderOriginalPreview(int $fileId): string`. Resolve OpenRegister `FileService` (installed-check + container), guard that the resolved node is a `File` and that `anonymizeEmlStructured` exists, call it with an EMPTY entity set, and assemble via `EmlPdfAssemblyService::assemble()`. Return PDF bytes; write no file. Let `ConversionFailedException` propagate.
- [x] 3. Create `lib/Controller/EmlPreviewController.php::preview(int $fileId)` (`#[NoAdminRequired]`, `#[NoCSRFRequired]`) returning a `DataDownloadResponse` (`application/pdf`) on success and a `422` `JSONResponse` on failure (log PII-free).
- [x] 4. Register the route `eml_preview#preview` → `GET api/anonymization/eml-preview/{fileId}` in `appinfo/routes.php`.
- [x] 5. Frontend service helpers in `src/services/fileViewerService.js`: `emlPreviewUrl(fileId)` (builds the endpoint URL) and `fetchUrlAsArrayBuffer(url)` (axios arraybuffer GET).
- [x] 6. `src/components/viewers/PdfViewer.vue`: add optional `url` prop; when set, load bytes via `fetchUrlAsArrayBuffer(url)` instead of the WebDAV `path`; watch `url` to reload.
- [x] 7. `src/views/fileViewer/FileViewerPage.vue`: `detectViewer()` maps `message/rfc822` / `.eml` → `eml`; map `eml` → `PdfViewer`; add a `viewerProps` computed that binds `{ path, url: emlPreviewUrl(fileId) }` for EML and `{ path }` otherwise; bind the dynamic component with `v-bind="viewerProps"`.
- [x] 8. Unit tests `tests/unit/Service/EmlPreviewServiceTest.php`: renders via the assembly service and passes an EMPTY entity set; guards throw when OpenRegister is missing, the node is not a `File`, or the anonymise-EML API is absent (assembly never invoked in the failure cases).
- [x] 9. Rebuild the frontend bundle and confirm the preview URL is present.
- [ ] 10. Frontend component/e2e test for the EML viewer routing (`detectViewer` → `eml`, `viewerProps` wiring) — deferred; covered manually for now.

## Acceptance Criteria

- The anonymisation upload widget accepts `.eml` / `message/rfc822`; other unsupported formats are still rejected.
- `GET /api/anonymization/eml-preview/{fileId}` returns a PDF/A-3b of the original message for an authenticated user, writes no file, and 422s on failure or when OpenRegister is unavailable.
- The render uses an empty entity set (no redaction) and the `eml-pdf-assembly` `EmlPdfAssemblyService` (no second render path).
- Opening an `.eml` in the file viewer shows the rendered preview instead of the "cannot be previewed" fallback; the original/anonymised toggle works for EML.

## Quality and Verification

- Run `composer check:strict` — clean for touched files (`EmlPreviewService`, `EmlPreviewController`): phpcs/psalm/phpstan + unit tests.
- Manual smoke against a live stack: upload an `.eml`, confirm the viewer renders the original; anonymise it and confirm the toggle switches between the original preview and the anonymised PDF.
- CHANGELOG under "Added": EML files can be uploaded and their original message previewed in the file viewer (server-rendered PDF).
- Document the NL-only envelope-label limitation (inherited from `eml-pdf-assembly`); EN follows `register-i18n`.
- Run `openspec validate "eml-viewer-preview"` — clean.