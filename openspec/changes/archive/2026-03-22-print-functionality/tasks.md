## 1. Font Bundling

- [x] 1.1 Download DejaVu Sans TTF files (regular, bold, oblique, bold-oblique) and place in `lib/Fonts/`
- [x] 1.2 Add LGPL license file for DejaVu Sans fonts in `lib/Fonts/LICENSE`

## 2. PdfService PDF/A Support

- [x] 2.1 Add `pdfa` option handling to `buildMpdfConfig()` — set `PDFA: true` and `PDFAauto: true` when enabled
- [x] 2.2 Add font embedding config to `buildMpdfConfig()` — set `fontDir` and `fontdata` for DejaVu Sans when PDF/A mode is active
- [x] 2.3 Add print-optimized CSS injection method `buildPrintCss(string $format, string $orientation): string`
- [x] 2.4 Inject print CSS into HTML in `generatePdf()` when PDF/A mode is enabled
- [x] 2.5 Add XMP metadata support — set `SetTitle()`, `SetAuthor()`, `SetCreator()` when PDF/A mode is active

## 3. PdfController PDF/A Endpoint

- [x] 3.1 Add `renderPdfA()` method to `PdfController` that forces `pdfa: true` in options
- [x] 3.2 Register `POST /api/pdf/render-pdfa` route in `appinfo/routes.php`

## 4. PrintController and Preview

- [x] 4.1 Create `lib/Controller/PrintController.php` with `preview()` method returning rendered HTML with print CSS
- [x] 4.2 Create `downloadPdfA()` method in `PrintController` delegating to `PdfService` with `pdfa: true`
- [x] 4.3 Add template resolution logic — support both `templateId` (via TemplateService) and inline `template` content
- [x] 4.4 Register `POST /api/print/preview` and `POST /api/print/pdf-a` routes in `appinfo/routes.php`

## 5. Frontend PrintPreview Component

- [x] 5.1 Create `src/components/PrintPreview.vue` with iframe-based HTML preview display
- [x] 5.2 Add "Print" button triggering `window.print()` on the iframe content
- [x] 5.3 Add "Download PDF/A" button calling `POST /api/print/pdf-a` and triggering file download
- [x] 5.4 Wire PrintPreview component into template detail view or standalone route

## 6. Quality and Testing

- [x] 6.1 Run `composer check:strict` and fix any PHPCS/PHPMD/Psalm/PHPStan issues in new/modified files
- [x] 6.2 Verify PDF/A output validity (check XMP metadata, font embedding, PDF/A-3b marker in output)
- [x] 6.3 Test backward compatibility — existing `renderPdf()` calls without `pdfa` option produce unchanged output
