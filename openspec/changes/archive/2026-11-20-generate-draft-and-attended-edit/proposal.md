---
kind: code
depends_on: [guided-document-wizard]
---

# Proposal: generate-draft-and-attended-edit

Matrix rows `gen-attended` and `gen-draft` in filinq's
`openspec/parity/capabilities.json`, both rated no, built.state none.
Written in the OpenSpec pass of 27 September 2026.

## Why

A clerk makes a letter on `/correspondence` and gets a download at once.
`CorrespondenceIndex.vue:336 generate()` calls the store, and
`src/store/modules/correspondence.js:77 generate()` posts to
`api/correspondence/generate` and hands the blob to `_triggerDownload()`
(:105, defined at :215). Nothing sits between rendering and storing. To
change one sentence the clerk edits the downloaded file elsewhere, or edits
the template, which changes the letter for everybody.

The template preview does not help. `src/views/templates/TemplateDetail.vue:211-215`
shows the rendered sample with `v-html` in a read-only block.

A letter also cannot wait. The open change `guided-document-wizard` keeps a
wizard run in the browser and puts resuming out of scope:
`openspec/changes/guided-document-wizard/proposal.md:120` "Multi-user /
resumable wizard sessions persisted server-side (a run is one clerk, one
sitting; abandoning the browser discards the draft)." Its design gives the
reason at design.md:296: "server drafts would create a new PII store". A
clerk who is called away halfway starts again. This change builds that
server draft, and answers the PII concern: the draft is private to its
maker and removed after 30 days.

Both rows sit in generate, filinq's core area.

### Matrix rows (filinq openspec/parity/capabilities.json)

| row | capability | filinq today |
|---|---|---|
| `gen-attended` | Edit a generated document by hand before it is stored. | no: the preview is read-only (`TemplateDetail.vue:211-215`) and correspondence downloads the blob at once (`correspondence.js:105`) |
| `gen-draft` | Save a half-finished document and finish it later. | no: `grep -rniE 'draft\|resume' lib src` finds only the correction draft of a final document (`lib/Service/FinalDocumentCorrectionService.php:67`); the wizard puts resumable runs out of scope |

### Competitors rated yes

- SmartDocuments (docs-only, analysed 2026-03-14), for `gen-attended`:
  "Attended: Opens SmartDocuments wizard in browser for user editing /
  Unattended: Generates document directly without user interaction".
  Evidence: `concurrentie-analyse/procest/dimpact-zac/specs/smart-documents-integration/spec.md:42`
  (the ZAC integration spec).
- SmartDocuments (docs-only, read 2026-09-26), for `gen-draft`: SmartWizard
  "Draft auto-save functionality". Evidence:
  https://smartdocuments.com/productfeatures/

## What changes

- An "Open for editing" action next to "Generate letter" on
  `/correspondence`, and on the review step of the wizard. filinq merges the data, renders the
  document as HTML and saves it as a draft. Nothing is filed and nothing is
  logged as generated.
- The draft opens in an editor. The clerk changes the text and saves.
- "Finish" turns the edited HTML into the chosen format, then files and
  logs it through the same code as a direct generation. The record says the
  document was edited by hand and by whom.
- A clerk can leave a draft and come back to it. "My drafts" lists the
  clerk's open drafts. A wizard draft saved halfway reopens at the question
  it stopped at.
- A draft is an OpenRegister object in the `filinq` register, private to
  the clerk who made it. It is removed 30 days after its last change. It is
  never kept in browser storage.
- The edited HTML is never run through Twig again. Text a clerk types stays
  text, even when it looks like a merge field.

## Capabilities

### New capabilities

- `generation-drafts`: a generation saved as an editable draft and
  finished later into a stored, logged document.

### Modified capabilities

None. `letter-correspondence-generation` and `guided-document-wizard` keep
their direct path. This change adds a second way to finish.

## Impact

- `lib/Settings/filinq_register.json`: new schema `documentDraft`;
  `generatedDocument` and `correspondence` gain `draftId` and
  `editedBy`. Register version bump.
- New `lib/Service/DocumentDraftService.php` and
  `lib/Controller/DocumentDraftController.php` with two routes:
  `POST api/documents/drafts` and `POST api/documents/drafts/{id}/finish`.
- `lib/Service/DocumentService.php`: a public method that produces, files
  and logs a document from finished HTML, sharing the tail of
  `generateDocument()`.
- New `lib/BackgroundJob/DocumentDraftExpiryJob.php`.
- `src/views/correspondence/CorrespondenceIndex.vue` and the wizard runner
  of `guided-document-wizard` (`src/views/wizard/WizardRunner.vue`, new
  there): the "Open for editing" and "Save and finish later" actions.
- New `src/views/drafts/DraftIndex.vue` (`/drafts`) and
  `src/views/drafts/DraftEditor.vue` (`/drafts/:id`), and two pages in
  `src/manifest.json`.
- `docs/features/`: a section with a screenshot.

## Out of scope

- Two people editing one draft at once. A draft belongs to one clerk.
- Handing a draft to a colleague. The clerk finishes it or discards it.
- Editing in an office suite. The draft is HTML in filinq's own editor;
  editing a stored office file is `document-rich-editing`.
- Drafts of a template that declares a plain-language counterpart. Finishing
  one would file the formal letter without its plain rendition, which
  `documents-in-and-out-of-the-building` forbids. The action is not offered
  for such a template in this change.
- Batch generation. A draft is one document.
