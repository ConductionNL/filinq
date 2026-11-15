---
kind: code
---

# Proposal: case-documents-and-the-flat-list

Round 4 discovery cluster 21, "Documents on the case: viewing, editing
and the flat list" (`procest/_round4/discovery/build-plan.md` in
ConductionNL/market-intelligence, 2026-09-14). Owner filinq, size M, no
dependency and no decision blocks it. The cluster's mechanism line:
extend `files-browser-columns` and `documents-on-the-case`; dossiq
consumes the leaf. Those two live elsewhere, `files-browser-columns` in
nextcloud-vue and `documents-on-the-case` in dossiq, so what filinq
builds is the document side underneath both.

## Why

A dossier is read two ways and both questions get asked. A handler wants
the document records, with their type, their status and their grondslag.
A jurist wants every file on the case in one flat list, because the
attachment they need is inside a record whose name says nothing about it.
dossiq has one view, `case-files`, and no second answer.

## Candidates

Eleven, all of them. Two are `must` and one is a matrix hole.

| candidate | relevance | driven passers | dossiq |
|---|---|---|---|
| C-documents-36 folder provisioned per unit or case domain, permissions kept in step | must, matrix hole | opencase, openproject | no |
| C-documents-22 one document store fed by every channel and application | must | none, pinkroccade and visma-circle documented | partial |
| C-documents-26 flat file list across the case's documents | should | opencase | partial |
| C-documents-42 administered upload file types and size limit | should | glpi, osticket | no |
| C-documents-1 edit a case document in the desktop application | should | xxllnc-zaken | no |
| C-documents-27 one document in several case domains without copying | should | plane | no |
| C-documents-19 external document store used as the file store | should | none, atabix documented | no |
| C-documents-10 resumable upload | could | tuleap | partial |
| C-documents-14 my documents list | could | opencase | no |
| C-documents-17 incomplete uploads reaped | could | plane | no |
| C-documents-8 upload detached from the write that attaches it | could | redmine | yes |

The cluster reads 11 passers, 8 driven and 3 documented, proving system
openproject.

## The decisions it rests on

- **D6**, relevance-led promotion. Both `must` members enter, and
  C-documents-22 enters on documented passers alone because it is a
  `must`.
- **D21**, documented-only candidates admitted and labelled.
  C-documents-22 and C-documents-19 have no driven passer and are marked
  documented wherever they appear.
- **D17**, a broad market including MKB. "My documents" and the reaper
  are small, and small is not a reason to drop them.

## The proving passers

- **OpenProject** is the provisioned folder, measured: project settings
  Storages, with `modules/storages/app/models/storages/project_storage.rb`
  and `last_project_folder.rb`. It creates the folder and keeps its
  permissions in step, which is the half that matters.
- **OpenCase** is the flat file list (Case Files tab,
  `CaseDetail-Files.md`), the my-documents list (`MyDocuments.md`) and
  the second passer on the provisioned folder.
- **GLPI** administers the upload types: Setup, Dropdowns via
  `front/documenttype.list.php` and `src/DocumentType.php:54-65`, with
  `is_uploadable`, `ext` and `mime`. osTicket is the second passer.
- **Plane** is both small ones: Project Pages at `db/models/page.py:23`
  are workspace-owned and linked to projects through `ProjectPage` at
  `:135`, which is one document in several domains; and
  `bgtasks/file_asset_task.delete_unuploaded_file_asset` reaps the
  uploads that never completed.
- **Tuleap** resumes an interrupted upload over tus.
- **xxllnc Zaken** edits a case document in the local desktop
  application through its Documentwatcher module.

## What filinq builds

- **A flat file list across a case's documents.** One leaf, listing every
  file on every document record of a case, with the record it belongs to
  as a column. The record view stays what it is; this is the second
  question, answered separately.
- **A folder provisioned per unit or case domain, permissions kept in
  step.** filinq creates the folder when the domain is created and
  reconciles its permissions when the domain's change. A DMS whose folder
  rights drift from the case's rights is an AVG incident waiting to be
  found, which is the candidate's own clause and the reason this is the
  matrix hole in the cluster.
- **Administered upload policy.** Which extensions and media types may be
  uploaded, and how large, declared by an administrator and enforced at
  the upload, not only listed in `DocumentDefaults.php`.
- **A document record in more than one domain.** A record carries its
  domains rather than being copied into each, so an advies that serves
  three zaaktypen is one record with one version history.
- **A my-documents list.** The documents a person created, as records,
  not as files in a folder.
- **Reaping incomplete uploads.** A nightly job removes upload fragments
  that never completed, and says how much it removed.
- **Desktop editing and resumable upload, stated honestly.** Both ride
  Nextcloud: desktop editing through the sync client on the case folder,
  resumable upload through Nextcloud's chunked upload. filinq declares
  that they work and tests that they do, rather than building either.
- **An external file store.** The case folder may live on a storage
  Nextcloud mounts, so a municipality that keeps documents in the
  Microsoft stack it already runs gets the same records over a different
  store.

## How dossiq consumes it

dossiq places the flat-file-list leaf beside the document records on its
Files tab, which `documents-on-the-case` builds over `CnFilesBrowser`.
The columns that list carries come from nextcloud-vue's
`files-browser-columns`, which is already on `development` and reads
values the node does not have, such as filinq's document projection. The
case folder is the folder dossiq already writes into; what changes is who
creates it and who keeps its permissions right.

## Existing specs it extends

`document-register` (the document record), `dossier-register` (the
domain a folder is provisioned for), `document-preview` and
`document-editing` (viewing and editing), `files-confidential-labels`
(the labels a column shows), `office-suite-portability` (the desktop
path) and `filinq-or-adoption` (everything stored through OpenRegister).

## ADRs

- ADR-001 and ADR-070: the document record and the domain are
  OpenRegister objects; no filinq table.
- ADR-075: filinq owns the document channel for the fleet.
- ADR-066: the flat list reaches dossiq as a leaf.
- ADR-012: the list renders with `@conduction/nextcloud-vue` components,
  never a bespoke table.

## Size

M. One leaf, one provisioning service, one policy, one reaper, over a
document register that exists.

## Dependencies

None blocking. nextcloud-vue's `files-browser-columns` is on
`development` and gives the flat list its columns.

## Out of scope

- Generating a document from a template. Sibling change
  `documents-from-a-template`.
- The archiving process after filing. Moved to openregister by D7.
- Redaction before a document leaves. Sibling change
  `redaction-and-what-leaves-the-building`.
- Building a sync client or an office suite. Nextcloud has both.
