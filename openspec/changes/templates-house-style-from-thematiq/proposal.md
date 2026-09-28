---
kind: code
depends_on: []
---

# Proposal: templates-house-style-from-thematiq

Half handed to filinq by thematiq `surfaces-document-house-style` (matrix row
`sur-generated-documents` in thematiq's `openspec/parity/capabilities.json`,
owned by thematiq). Written in the owner-moves pass of 28 September 2026.

## Why

An organisation sets its logo, colours and fonts in thematiq and then sets
them again in filinq's `huisstijl`, and the two drift apart. A letter
generated without an explicit `huisstijlId` gets no house style at all.
thematiq's change `surfaces-document-house-style`, merged on thematiq
`development`, publishes a document house style profile for exactly this, and
names filinq's half.

thematiq `surfaces-document-house-style`, Impact: "Sibling halves, not in
this repository: filinq seeds and refreshes its `huisstijl` object from the
profile (`lib/Service/DocumentRenderPipeline.php` `loadHuisstijl()` at filinq
`7af2f955`)". Its design: "fleet apps resolve
`OCA\Thematiq\Service\DocumentStyleService` from the server container when
thematiq is installed ... Over HTTP: `GET /apps/thematiq/api/document-style`
for the signed-in user", and "Per-group house styles carry through".

Row `sur-generated-documents`, "Have documents the system generates
(letters, PDF exports, reports) carry the house style: logo, cover, footer
and fonts." Tender demand: TenderNed 404703 (Hilversum VTH), 415897 (FUMO)
and 298070 (BUCH, several house styles, one per municipality)
(https://www.tenderned.nl/aankondigingen/overzicht/404703). M365
organisational branding and openDesk rate partial.

filinq's own row `tpl-house-style` ("Apply the organisation's house style to
every generated document from one place", building, SmartDocuments yes)
names the same goal; this change is its thematiq half, not its editing
screen.

## What changes

- filinq keeps one `huisstijl` per thematiq token set, marked as managed by
  thematiq, and refreshes it from the profile when the profile changes.
- The `huisstijl` schema gains the profile's fonts, cover image and footer
  lines.
- A document generated without a `huisstijlId` uses the managed house style
  of the requesting user's token set, so the BUCH municipalities each get
  their own letters.
- A managed house style is read-only in filinq; a hand-made one stays as it
  is and still wins when named.

## Out of scope

- The profile, its settings and the per-group resolution (thematiq).
- Logo, fonts and footer in OpenRegister's PDF exports (OpenRegister's half
  of the same thematiq change).
- An editing screen for hand-made house styles (row `tpl-house-style`).

## Impact

- New `lib/Service/HuisstijlProfileSync.php`; changes to
  `lib/Service/DocumentRenderPipeline.php` (`loadHuisstijl()`),
  `lib/Service/CorrespondenceService.php` (`buildPdfOptions()`, the header
  and footer render) and `lib/Settings/filinq_register.json` (`huisstijl`
  1.2.0, register version bump).

## Cross-app dependencies

- thematiq `surfaces-document-house-style`: `DocumentStyleService::forUser()`
  and its profile shape. Without thematiq nothing changes in filinq.
