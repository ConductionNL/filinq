# Design: generate-draft-and-attended-edit

Kind: code. One schema, one service, two routes, two screens, and one
action in two existing places.

## Context

Read at development `2088cc1f`.

- `lib/Service/DocumentService.php:168` `generateDocument()` resolves data,
  renders HTML through `DocumentRenderPipeline::renderWithHuisstijl()`
  (`lib/Service/DocumentRenderPipeline.php:144`), turns it into bytes with
  `produceOutput()` (:197), files it with the private
  `storeOutputIfRequested()` (:734) and logs a `generatedDocument` through
  `GeneratedDocumentLogger::log()` (called at :247). Valid formats are
  `pdf`, `odf`, `html` (:71); output modes `return`, `files`, `both` (:85).
- `DocumentService::generatePreview()` (:305) does the first half only:
  resolve and render to HTML, no audit entry. It is the render step a draft
  needs.
- `lib/Service/CorrespondenceService.php:205` `generate()` is a second
  path for letters with its own render and produce copies (:617, :666) and
  logs a `correspondence` object through the private `logCorrespondence()`
  (:798). Valid formats are `pdf`, `docx`, `html`, `email` (:78).
- `src/store/modules/correspondence.js:77` `generate()` posts to
  `api/correspondence/generate` and downloads the blob with
  `_triggerDownload()` (:105, :215). `CorrespondenceIndex.vue:122-135`
  holds the one "Generate letter" button.
- `src/views/templates/TemplateDetail.vue:162-169` is a `contenteditable`
  editing area over template HTML with a raw-HTML toggle (:171-187). The
  preview at :211-215 is read-only.
- The `template` schema carries `plainLanguage`, the plain-language
  counterpart that `generateDocument()` plans before filing (:198-202).
- `generatedDocument` (`lib/Settings/filinq_register.json:1125`, version
  1.3.0) and `correspondence` (:901, version 1.2.0) have no draft or editor
  fields.
- The register description records why working state carries no
  `x-openregister-archival` annotation: it "makes user-driven deletes throw
  ArchivalImmutableException" (v7.11.0 note, :5). OpenRegister's
  `ArchivalRetentionTask` also counts retention from `_created`
  (`ro-openregister lib/BackgroundJob/ArchivalRetentionTask.php`, header).
- OpenRegister's `authorization.scope: private` admits the owner and
  administrators only (`ro-openregister lib/Service/Rbac/ObjectScopeResolver.php:73,87,107`).
- `guided-document-wizard` (open) adds `src/views/wizard/WizardRunner.vue`
  with a review step (its design.md D6) and `options.wizardContext` on the
  generate request (its D4).

New: `documentDraft`, `DocumentDraftService`, `DocumentDraftController`,
`DocumentDraftExpiryJob`, `DraftIndex.vue`, `DraftEditor.vue`.

## Goals / Non-goals

Goals: edit the rendered document before it is stored; leave a generation
and resume it; keep one filing and logging path; keep a draft private and
short-lived.

Non-goals: shared drafts, office-suite editing, batch drafts, drafts of a
template with a plain-language counterpart.

## Decisions

### D1. A draft is an OpenRegister object, not browser state

`documentDraft` in the `filinq` register holds the request (template,
data references, ad-hoc data, format, output options, origin), the stage,
and for a wizard draft the answers and the current step. Alternative
considered: IndexedDB in the browser. Rejected: it is lost on another
device or a cleared browser, and the row asks for a draft the clerk can
come back to.

### D2. Private to its maker, removed after 30 days of rest

The schema declares `authorization.scope: private`, so only the clerk who
made the draft and an administrator can read it. `expiresAt` is set to 30
days after every save. `DocumentDraftExpiryJob`, a daily TimedJob,
deletes drafts past `expiresAt`. Alternative considered: an
`x-openregister-archival` retention. Rejected on two facts above: it counts
from creation, so a draft in use would vanish, and it blocks the clerk's
own discard.

### D3. Rendering reuses the preview; finishing reuses the tail

Creating a draft calls `DocumentService::generatePreview()`. Finishing
calls a new public `DocumentService::generateFromHtml()` that runs the
tail of `generateDocument()` unchanged: `produceOutput()`,
`storeOutputIfRequested()`, the logger. A correspondence draft is logged
through a new public `CorrespondenceService::logFinishedDraft()` that
wraps `logCorrespondence()`. Alternative considered: a draft flag inside
`generateDocument()`. Rejected, because a draft stops halfway and resumes
in another request, and one method that does both halves on a flag is
harder to read than two calls.

### D4. The edited HTML is never rendered by Twig again

Finishing produces bytes from the stored HTML directly. A clerk who types
`{{ naam }}` gets those characters in the letter. The HTML is sanitised
with DOMPurify in the editor and again on the server before
`produceOutput()`, keeping the tags the huisstijl uses. Alternative
considered: store an edited template and render it. Rejected: it gives a
clerk template execution through a text box.

### D5. One route for create, one for finish, the rest through OpenRegister

`POST api/documents/drafts` renders and saves. `POST
api/documents/drafts/{id}/finish` produces, files, logs and closes.
Saving an edit and discarding a draft are plain object writes and deletes
through OpenRegister's object API from the page (ADR-022). Alternative
considered: a save and a delete route in filinq. Rejected as pass-through
CRUD.

### D6. A finished draft is closed

The stage moves `answering` to `editing` to `finished`, declared as an
`x-openregister-lifecycle` on `stage`. `finished` is terminal. The finished
draft keeps its link from `generatedDocument.draftId` or
`correspondence.draftId` until the expiry job removes it; the generated
record is the lasting evidence.

### D7. The editor is the template editor's surface

`DraftEditor.vue` reuses the `contenteditable` area and raw-HTML toggle of
`TemplateDetail.vue:162-187`, moved into a shared component
`src/components/HtmlDocumentEditor.vue`, inside nextcloud-vue page
components (ADR-012). Merge-field and condition buttons are not offered: a
draft has no merge fields left.

### D8. A plain-language template is refused

`POST api/documents/drafts` answers 409 "This template has a plain-language
version. Generate it directly." when the template declares
`plainLanguage`, and the page does not show the action for it.

## Declarative-vs-imperative decision (ADR-031)

| concern | choice | why |
|---|---|---|
| draft stage | `x-openregister-lifecycle` on `stage` | a plain state machine with one terminal state |
| rendering and finishing | imperative service | data resolution and file production, no object lifecycle |
| expiry | imperative TimedJob | the archival annotation counts from creation and blocks discard; a ScheduledWorkflow needs n8n for one delete query |
| access | schema `authorization.scope: private` | OpenRegister enforces it on every read path |
| notification | none | the clerk who made the draft is the one who finishes it |

## Seed data

New `documentDraft` (1.0.0): `origin` (`correspondence`, `document`,
`wizard`), `stage`, `templateId`, `templateVersion`, `dataRefs`,
`adHocData`, `format`, `outputOptions`, `html`, `wizardId`,
`wizardAnswers`, `wizardStep`, `warnings`, `expiresAt`,
`finishedDocumentId`. `generatedDocument` (to 1.4.0) and `correspondence`
(to 1.3.0) gain `draftId` and `editedBy`. The seed adds one `editing`
draft of the `brief-algemeen` template owned by the demo user, so
`/drafts` is not empty on a fresh install.

## Risks / trade-offs

- A draft holds personal data from the merged records. It is private, it
  expires, and `documentDraft` declares an `x-openregister-processing`
  activity with purpose "preparing a document" and the 30-day retention
  (AVG art. 5(1)(e) storage limitation, art. 25 data protection by design).
- A clerk edits a letter into something the template owner would not
  allow. The record names the editor, and a final document still goes
  through the existing finality rules.
- The source data changes after the draft was made. The draft keeps what
  was merged at the time; the editor shows the moment it was rendered.

## Open questions

- Should a manager be able to see a clerk's open drafts, for example when
  the clerk is away? This change says no; an administrator can.
