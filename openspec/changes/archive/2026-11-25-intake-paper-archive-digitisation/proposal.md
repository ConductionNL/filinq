---
kind: code
depends_on: [scan-intake-with-separator-sheets, inbound-documents-and-the-worklist]
---

# Proposal: intake-paper-archive-digitisation

Matrix row `in-paper-archive` in filinq's `openspec/parity/capabilities.json`,
rated no, built.state none. Written in the OpenSpec pass of 27 September
2026.

## Why

A municipality decides to scan the building permits of 1970 to 2000: four
hundred boxes in the basement. Today filinq can cut a scanned batch at
separator sheets (`ScanBatchService`, open change
`scan-intake-with-separator-sheets`), but every segment lands in the intake
inbox as a new arrival, to be assigned by hand. Nothing knows that the
segment is the digital copy of one paper original in box 117, nothing
records that the original was scanned and checked, and nothing tells the
archivist which originals are done.

`inbound-documents-and-the-worklist` names this as its candidate
C-intake-13, "retro-digitisation of a paper archive, with OCR and metadata",
rated partial in the gap register (`proposal.md:38`), without a change
behind it. A search of `lib/` and `src/` for `digitis*`, `paperOriginal` or
a paper archive finds nothing.

The row sits in intake, filinq's core area.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `in-paper-archive` | Digitise a paper archive in batches and mark each paper original as digitised. | no: batch splitting exists for arriving post only; no project, no paper original, no digitised status |

### Demand

None recorded.

### Competitors rated yes

- Decos JOIN (docs-only, read 2026-09-26): "paper archive digitisation
  support ... A paper archive is digitised into the digital one, with
  scanning, OCR and metadata." Evidence:
  https://decos.com/oplossingen/rma-archivering
- Paperless-ngx is partial: "batch scans via consume folder and barcode
  splitting ... ASN holds the paper original's binder position ... No
  'original digitised' status, only a hand-made custom field"
  (https://github.com/paperless-ngx/paperless-ngx, v3.2.1).

## What changes

- An archivist creates a digitisation project: which archive, which boxes,
  which scan profile, which record the documents belong to, and the share
  of scans to check by eye.
- Per box the archivist registers the paper originals, or imports the box
  list as CSV: shelf mark, description, date range.
- filinq prints one separator sheet per original. Its QR code names the
  original, so the scanner's batch is cut into documents that already know
  which original they copy.
- A cut segment for a project does not go to the intake inbox. It is filed
  on the project's record with the original's metadata, and the text is
  recognised through the existing OCR path.
- The original is marked scanned, then checked: a checker compares the scan
  with the paper for the sampled share and passes it or sends it back to be
  scanned again.
- The project page shows per box how many originals are registered,
  scanned, checked and rejected.

## Capabilities

### New capabilities

- `paper-archive-digitisation`: digitisation projects, paper originals with
  a scanned and checked status, separator sheets per original, and filing
  of the scans on the project's record.

### Modified capabilities

None. The separator payload of `scan-intake-with-separator-sheets`
(REQ-SCI-02) gains an optional original reference in a later version tag;
the existing payload keeps working.

## Impact

- `lib/Settings/filinq_register.json`: new `digitisationProject` and
  `paperOriginal` schemas with lifecycles; version bump and seed.
- `lib/Service/SeparatorSheetService.php`: a sheet per original with
  payload `filinq:sep:v2:<profileId>:po:<uuid>`.
- `lib/Service/ScanBatchService.php`: a segment whose separator names an
  original is filed on the project's record instead of the inbox.
- New `lib/Service/DigitisationProjectService.php` (CSV import, sampling,
  counts).
- `src/views/`: a project index and detail with per-box counts and the
  checking queue, in the manifest.

## Cross-app dependencies

- openregister: substituting the paper original (vervanging) and destroying
  it after a substitution decision is the archiving process, which lives in
  openregister (decision D7 recorded in `competitor-parity-2026-09`). filinq
  marks the original checked; openregister decides what happens to the
  paper.

## Out of scope

- The substitution decision and destruction of paper (openregister, above).
- Scanning hardware settings beyond the existing scan profiles.
- Handwriting recognition (`det-handwriting`, deferred).
