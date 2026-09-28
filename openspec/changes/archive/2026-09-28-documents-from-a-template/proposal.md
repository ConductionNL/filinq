---
kind: code
---

# Proposal: documents-from-a-template

Round 4 discovery cluster 30, "Documents generated from a template, and
how they look" (`procest/_round4/discovery/build-plan.md` in
ConductionNL/market-intelligence, 2026-09-14). Owner filinq, size M, no
dependency and no decision blocks it. The cluster's mechanism line:
extend filinq `document-generation-vendor-adapter` and
`merge-documents-to-pdf`.

## Why

A besluit that leaves the building on the wrong briefpapier is a formal
defect, not a cosmetic one. Filinq generates documents and has no
administered page layout, no header, no footer, and no way to make a
periodic document out of the records a search returned. Eight
capabilities in this cluster, and dossiq fails seven.

## Candidates

Eight, all of them. One is `must`.

| candidate | relevance | driven passers | dossiq |
|---|---|---|---|
| C-documents-25 download all case files as one archive | must | redmine | no |
| C-documents-34 administered page layout for case PDF output | should | openproject, easy-redmine documented | no |
| C-documents-7 file as the value of a case-type field | should | xxllnc-zaken | no |
| C-documents-12 review-again date on a released document | should | huly | no |
| C-documents-2 signature verification with the signer's trust shown | should | forgejo, gitea | partial |
| C-documents-13 submitted form filed as a document | should | none, jira-service-management documented | no |
| C-documents-4 periodic report document generated from a template | could | none, jira-data-center documented | no |
| C-decisions-8 post-decision review as its own record, from a template | should | none, jira-service-management documented | no |

The cluster reads 9 passers, 6 driven and 3 documented, proving system
forgejo.

## The decisions it rests on

- **D6**, relevance-led promotion. C-documents-25 is the cluster's `must`
  and enters on one driven passer.
- **D21**, documented-only candidates admitted and labelled. Four of the
  eight have no driven passer: C-documents-13, C-documents-4,
  C-decisions-8 and the second half of C-documents-34. Each is labelled
  documented wherever it appears.
- **D17**, a broad market including MKB. A post-decision review and a
  periodic report are as much an SME artefact as a municipal one.

## The proving passers

- **OpenProject** administers the page layout for PDF output:
  `resources :pdf_export_template, path: "pdf_export"`, with a toggle,
  under Administration, Work packages. Easy Redmine claims the same,
  documented.
- **Redmine** downloads every attachment as one archive:
  `get 'attachments/:object_type/:object_id/download'`,
  `attachments#download_all`, capped by `bulk_download_max_size`,
  default 102400 KB. The cap is the part to copy.
- **Forgejo and Gitea** verify a signature and show the signer's trust:
  `/signing-key.gpg`, `/signing-key.ssh`, `/user/gpg_key_verify` and the
  Verified badge (`api.go`).
- **Huly** sets a review-again date on a released document:
  `controlled-documents reviewInterval` in months.
- **xxllnc Zaken** makes a file the value of a case-type field, inside
  the phase form (`case-detail-anatomy.md`).

## What filinq builds

- **An administered page layout.** A named layout carrying paper size,
  margins, header, footer, logo and the first-page difference, declared
  once and selected per template. A besluit then leaves on the right
  briefpapier because the layout is administered, not because the person
  generating it remembered.
- **Every file on a case as one archive.** One download, one bundle, with
  an administered size ceiling and a manifest listing what is inside and
  what was left out and why. A dossier handed to a bezwaarcommissie or a
  rechtbank goes as one bundle, and clicking twenty files is how one gets
  left out.
- **A periodic document from a saved search.** A template rendered over
  the records a named view returns, on a schedule or on demand. A
  besluitenlijst, a Woo-publicatielijst and a jaarverslag bijlage are all
  exactly this and are made by hand today.
- **A review-again date on a released document.** A released document
  carries a review interval, and the product asks when it expires. That
  is the whole of beleidsonderhoud.
- **A submitted form filed as a document.** The form as it was submitted,
  rendered through a template and filed on the record, so the
  aanvraagformulier is in the dossier as a document and not only as
  fields.
- **A file as the value of a field.** A document reference is a value a
  form field can hold, so "Overzicht DigiD-aansluitingen" is a field
  whose value is a document, which is most permit evidence.
- **Signature trust shown.** A verified signature shows who signed and
  what the verification actually proved, beside the document.
- **A review record from a template.** After it is over, an evaluation is
  a record of its own, from a template, and can be published.

## How dossiq consumes it

dossiq selects a layout per case type and per document template, and
places the Download all as archive action on its Files tab, which
`documents-on-the-case` builds. The besluitenlijst is a periodic document
over a dossiq saved view. The saved view it reads is the one
nextcloud-vue's `saved-view-tree-and-labels` gives a slug, so the
schedule names the view rather than restating its query. dossiq owns the
case type and the resultaattype; filinq owns the rendering.

## Existing specs it extends

`document-creatie-sjablonen` and `template-management` (the template and
its versions), `pdf-generation` and `pdf-conversion` (the rendering),
`pdfa3-conversion` (the archival profile), `document-signing` and
`signature-verification-portal` (the trust shown),
`generated-document-names-its-template-version` (what a generated
document records about how it was made) and `merge-documents-to-pdf` (the
bundle's PDF path).

## ADRs

- ADR-075: filinq owns document and PDF generation for the fleet, one
  channel. The layout belongs to that channel.
- ADR-001 and ADR-070: the layout, the schedule and the archive job are
  OpenRegister objects.
- ADR-066: the archive download and the periodic document reach a
  consuming app as leaves.
- ADR-031: the archive job's lifecycle is declared on its schema.

## Size

M. One layout object, one archive service, one scheduled render, over a
template engine and a PDF cascade that both exist.

## Dependencies

None blocking. `merge-documents-to-pdf` is on `development` and gives the
archive its PDF path. The periodic document reads a saved view by slug,
which nextcloud-vue's `saved-view-tree-and-labels` adds; until it lands,
a periodic document names a view by id.

## Out of scope

- Which documents a case type requires. dossiq's case type owns that.
- Publishing the result. opencatalogi owns publication.
- The archiving process after generation. Moved to openregister by D7.
