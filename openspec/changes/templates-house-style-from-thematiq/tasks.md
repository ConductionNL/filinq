# Tasks: templates-house-style-from-thematiq

<!-- HYDRA CAP: max 20 unindented `- [ ]` lines. This file uses 5. -->

## 1. Schema and sync

- [x] 1.1 Add `managedBy`, `tokenSetId`, `profileHash`, `fonts`, `coverImage` and `footerLines` to `huisstijl` (1.2.0) and bump the register version (REQ-HSP-001). Verify: `occ maintenance:repair` imports it and an existing house style still loads.
- [x] 1.2 Add `lib/Service/HuisstijlProfileSync.php` (REQ-HSP-001). Verify: PHPUnit with a stub profile creates one managed house style, writes nothing on an unchanged hash, and does nothing without thematiq.

## 2. Render

- [ ] 2.1 Use the managed house style when no `huisstijlId` is given, and apply fonts and cover in `buildPdfOptions()` (REQ-HSP-002). Verify: PHPUnit renders a letter without an id and finds the profile's footer line; a local run with thematiq and two groups on two token sets gives two different letters, noted in the PR body.
- [x] 2.2 Refuse an update of a managed house style through filinq (REQ-HSP-003). Verify: PHPUnit and a Newman call answering 409 with the message.

## 3. Docs

- [ ] 3.1 Add a section to `docs/features/` on house styles from Theming, with a screenshot of a letter in the house style (REQ-HSP-002). Verify: the docs page renders with the screenshot.

## Build notes (continuation)

- 2.1: `PdfService::applyHouseStyle()` now applies `pdfAssets()` to the PDF:
  the heading and body font families as CSS with DejaVu Sans after them, and
  the cover image on its own page for a template with `coverPage`. Left open:
  embedding the uploaded font file itself. thematiq serves it only through an
  authenticated route (`thematiq.font.serve`), which mPDF cannot fetch, and
  mPDF needs a TTF/OTF file on disk; that needs thematiq to hand filinq the
  file, not a URL. The local run with two token sets was not done.
  Checked again (c3): thematiq's `FontService` accepts and stores WOFF2
  only (`FontValidator` refuses anything without the `wOF2` magic), and mPDF
  reads TTF/OTF only, so even reading the bytes server side through
  `FontService::readFontBytes()` gives mPDF nothing it can embed. Embedding
  the uploaded font needs a TTF/OTF from thematiq (a decision there), not
  more code in filinq; the PDF keeps the family name with DejaVu Sans as
  the embedded fallback.
- 3.1: the section is in `docs/features/letter-correspondence-generation.md`;
  the screenshot of a letter is not, so 3.1 stays open.
